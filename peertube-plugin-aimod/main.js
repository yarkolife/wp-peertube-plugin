async function register ({ registerHook, peertubeHelpers, registerSetting, settingsManager, getRouter, registerClientRoute }) {
  const { logger, database } = peertubeHelpers

  // Register plugin settings
  await registerSetting({
    name: 'endpoint',
    label: 'AI Endpoint URL',
    type: 'input',
    default: 'http://ai-moderator:8000/analyze',
    private: false,
    descriptionHTML: 'URL des AI-Moderation-Services. Muss innerhalb des Docker-Netzwerks erreichbar sein.'
  })

  await registerSetting({
    name: 'threshold',
    label: 'Toxizitäts-Schwelle (0–1)',
    type: 'input',
    default: '0.7',
    private: false,
    descriptionHTML: `
      <div style="margin-top: 8px; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #17a2b8;">
        <strong>Schwelle für allgemeine Toxizität (Primary Model)</strong>
        <p style="margin: 8px 0 0 0; font-size: 0.9em;">
          <strong>Empfohlene Werte:</strong><br>
          • <strong>0.7</strong> (Standard) - Blockiert nur explizite Beleidigungen<br>
          • <strong>0.5</strong> (Strikt) - Blockiert mehr aggressive Kommentare<br>
          • <strong>0.8</strong> (Mild) - Blockiert nur sehr explizite Beleidigungen
        </p>
      </div>
    `
  })

  await registerSetting({
    name: 'hate_threshold',
    label: 'Hate Speech Schwelle (0–1)',
    type: 'input',
    default: '0.5',
    private: false,
    descriptionHTML: `
      <div style="margin-top: 8px; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #dc3545;">
        <strong>Schwelle für Hate Speech (Secondary Model)</strong>
        <p style="margin: 8px 0 0 0; font-size: 0.9em;">
          <strong>Empfohlene Werte:</strong><br>
          • <strong>0.5</strong> (Standard) - Blockiert Hate Speech<br>
          • <strong>0.4</strong> (Strikt) - Blockiert mehr grenzwertige Kommentare<br>
          • <strong>0.6</strong> (Mild) - Blockiert nur sehr expliziten Hate Speech
        </p>
        <p style="margin: 8px 0 0 0; font-size: 0.85em; color: #6c757d;">
          <strong>Hinweis:</strong> Kommentare werden blockiert, wenn <strong>entweder</strong> die Toxizitäts-Schwelle <strong>oder</strong> die Hate Speech Schwelle überschritten wird.
        </p>
      </div>
    `
  })

  await registerSetting({
    name: 'non_german_action',
    label: 'Aktion für nicht-deutsche Kommentare',
    type: 'select',
    options: [
      { value: 'moderate', label: 'Zur Moderation zurückhalten' },
      { value: 'block', label: 'Blockieren' }
    ],
    default: 'moderate',
    private: false,
    descriptionHTML: `
      <div style="margin-top: 8px; padding: 12px; background: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107;">
        <strong>Verhalten für Kommentare in anderen Sprachen</strong>
        <p style="margin: 8px 0 0 0; font-size: 0.9em;">
          • <strong>Zur Moderation zurückhalten</strong> - Kommentare werden in die Moderations-Warteschlange verschoben (empfohlen)<br>
          • <strong>Blockieren</strong> - Kommentare werden sofort blockiert
        </p>
        <p style="margin: 8px 0 0 0; font-size: 0.85em; color: #856404;">
          <strong>Hinweis:</strong> Die AI-Modelle sind speziell für deutsche Sprache trainiert. Kommentare in anderen Sprachen können nicht zuverlässig auf Toxizität geprüft werden.
        </p>
      </div>
    `
  })

  // Initialize database table for blocked comments
  async function initDatabase () {
    try {
      const query = `
        CREATE TABLE IF NOT EXISTS "pluginGermanAiModBlockedComments" (
          "id" SERIAL PRIMARY KEY,
          "text" TEXT NOT NULL,
          "videoId" INTEGER,
          "videoUuid" VARCHAR(255),
          "userId" INTEGER,
          "userUsername" VARCHAR(255),
          "blockReason" VARCHAR(50) NOT NULL,
          "score" REAL,
          "hateScore" REAL,
          "language" VARCHAR(10),
          "createdAt" TIMESTAMP DEFAULT NOW(),
          "approved" BOOLEAN DEFAULT FALSE,
          "approvedBy" INTEGER,
          "approvedAt" TIMESTAMP
        )
      `
      await database.query(query)
      logger.info('[AI Mod] Database table initialized')
    } catch (err) {
      logger.error('[AI Mod] Error initializing database:', err)
    }
  }

  await initDatabase()

  // Save blocked comment to database
  async function saveBlockedComment (text, params, blockReason, score, hateScore, language) {
    try {
      const videoId = params?.video?.id || null
      const videoUuid = params?.video?.uuid || null
      const userId = params?.user?.id || null
      const userUsername = params?.user?.username || null

      const query = `
        INSERT INTO "pluginGermanAiModBlockedComments" 
        ("text", "videoId", "videoUuid", "userId", "userUsername", "blockReason", "score", "hateScore", "language")
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
        RETURNING "id"
      `
      const result = await database.query(query, [
        text,
        videoId,
        videoUuid,
        userId,
        userUsername,
        blockReason, // 'toxic' or 'non_german'
        score || null,
        hateScore || null,
        language || null
      ])
      logger.info(`[AI Mod] Blocked comment saved to DB with id: ${result.rows[0].id}`)
      return result.rows[0].id
    } catch (err) {
      logger.error('[AI Mod] Error saving blocked comment:', err)
      return null
    }
  }

  /**
   * Check comment with AI service
   * @param {string} text - Comment text
   * @returns {Promise<{allowed: boolean, score: number, requiresModeration: boolean, language: string}>}
   */
  async function checkCommentWithAI (text) {
    const endpoint = await settingsManager.getSetting('endpoint') || 'http://ai-moderator:8000/analyze'
    const thresholdStr = await settingsManager.getSetting('threshold') || '0.7'
    const threshold = Number(thresholdStr) || 0.7
    const hateThresholdStr = await settingsManager.getSetting('hate_threshold') || '0.5'
    const hateThreshold = Number(hateThresholdStr) || 0.5

    logger.info(`[AI Mod DEBUG] Settings - threshold: ${threshold}, hateThreshold: ${hateThreshold}, endpoint: ${endpoint}`)

    if (!text || !text.trim()) {
      logger.info('[AI Mod DEBUG] Empty text, allowing')
      return { allowed: true, score: 0, hateScore: 0, requiresModeration: false, language: 'unknown', isGerman: false }
    }

    logger.info(`[AI Mod DEBUG] Checking text: "${text.substring(0, 100)}..."`)

    // Timeout to prevent hanging requests
    const controller = new AbortController()
    const timeout = setTimeout(() => controller.abort(), 3000)

    try {
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ text }),
        signal: controller.signal
      })

      clearTimeout(timeout)

      if (!res.ok) {
        logger.error(`[AI Mod] HTTP ${res.status} from AI service`)
        return { allowed: true, score: 0, hateScore: 0, requiresModeration: false, language: 'unknown', isGerman: false } // fail-open
      }

      const data = await res.json()
      logger.info(`[AI Mod DEBUG] AI response: ${JSON.stringify(data)}`)
      
      const score = typeof data.score === 'number' ? data.score : 0
      const hateScore = typeof data.hate_score === 'number' ? data.hate_score : 0
      // Toxic if primary score >= threshold OR hate score >= hateThreshold
      // Note: AI service always returns toxic: false, plugin decides based on configured thresholds
      const toxic = (score >= threshold) || (hateScore >= hateThreshold)
      const requiresModeration = !!data.requires_moderation
      const language = data.language || 'unknown'
      // Ensure isGerman is explicitly boolean: true only if explicitly true, false otherwise
      const isGerman = data.is_german === true || data.is_german === 'true' || data.is_german === 1

      logger.info(`[AI Mod DEBUG] Calculated - toxic: ${toxic}, allowed: ${!toxic}, score: ${score}, hateScore: ${hateScore}, isGerman: ${isGerman}`)

      return {
        allowed: !toxic,
        score,
        hateScore,
        requiresModeration,
        language,
        isGerman
      }
    } catch (err) {
      clearTimeout(timeout)
      // If AI service is down - log error but don't break UX (fail-open)
      logger.error('[AI Mod] AI service error', { err: err.message || String(err) })
      return { allowed: true, score: 0, hateScore: 0, requiresModeration: false, language: 'unknown', isGerman: false }
    }
  }

  /**
   * Handle comment moderation based on AI result
   * @param {boolean} accepted - Current acceptance status
   * @param {object} params - Comment parameters
   * @returns {Promise<{accepted: boolean, errorMessage?: string, heldForReview?: boolean}>}
   */
  async function handleCommentModeration (accepted, params) {
    logger.info(`[AI Mod DEBUG] handleCommentModeration called - accepted: ${accepted}`)
    
    // Log video info if available
    if (params && params.video) {
      logger.info(`[AI Mod DEBUG] Video info - commentsPolicy: ${params.video.commentsPolicy}, comments: ${params.video.comments}`)
    }
    
    if (!accepted) {
      logger.info('[AI Mod DEBUG] Already rejected by another hook or PeerTube policy')
      return { accepted: false, errorMessage: 'Comment has been rejected by another moderation rule.' }
    }

    // Debug: log params structure safely
    if (params) {
      const paramKeys = Object.keys(params)
      logger.info(`[AI Mod DEBUG] params keys: ${paramKeys.join(', ')}`)
      
      // Try to log commentBody structure
      if (params.commentBody) {
        if (typeof params.commentBody === 'string') {
          logger.info(`[AI Mod DEBUG] commentBody is string: "${params.commentBody.substring(0, 50)}..."`)
        } else if (typeof params.commentBody === 'object') {
          const commentBodyKeys = Object.keys(params.commentBody || {})
          logger.info(`[AI Mod DEBUG] commentBody keys: ${commentBodyKeys.join(', ')}`)
          if (params.commentBody.text) {
            logger.info(`[AI Mod DEBUG] commentBody.text exists: "${String(params.commentBody.text).substring(0, 50)}..."`)
          }
        }
      }
    }

    // Try multiple ways to extract text
    let text = ''
    
    // Method 1: commentBody.text (object)
    if (params && params.commentBody) {
      if (typeof params.commentBody === 'string') {
        text = params.commentBody
      } else if (params.commentBody.text) {
        text = params.commentBody.text
      } else if (params.commentBody.body) {
        text = typeof params.commentBody.body === 'string' ? params.commentBody.body : params.commentBody.body.text || ''
      }
    }
    
    // Method 2: body.text
    if (!text && params && params.body) {
      if (typeof params.body === 'string') {
        text = params.body
      } else if (params.body.text) {
        text = params.body.text
      }
    }
    
    // Method 3: comment.text
    if (!text && params && params.comment) {
      if (typeof params.comment === 'string') {
        text = params.comment
      } else if (params.comment.text) {
        text = params.comment.text
      } else if (params.comment.body) {
        text = typeof params.comment.body === 'string' ? params.comment.body : params.comment.body.text || ''
      }
    }
    
    // Method 4: direct text
    if (!text && params && params.text) {
      text = params.text
    }
    
    // Method 5: req.body (for API requests)
    if (!text && params && params.req && params.req.body) {
      if (typeof params.req.body === 'string') {
        text = params.req.body
      } else if (params.req.body.text) {
        text = params.req.body.text
      } else if (params.req.body.commentBody) {
        text = typeof params.req.body.commentBody === 'string' 
          ? params.req.body.commentBody 
          : params.req.body.commentBody.text || ''
      }
    }

    // Clean up text - remove HTML tags if present
    text = (text || '').trim()
    if (text && text.includes('<')) {
      // Simple HTML tag removal (basic, but should work for most cases)
      text = text.replace(/<[^>]*>/g, '').trim()
    }

    logger.info(`[AI Mod DEBUG] Extracted text from params: "${text.substring(0, 100)}${text.length > 100 ? '...' : ''}" (length: ${text.length})`)

    const { allowed, score, hateScore, requiresModeration, language, isGerman } = await checkCommentWithAI(text)

    logger.info(`[AI Mod DEBUG] checkCommentWithAI result - allowed: ${allowed}, score: ${score}, hateScore: ${hateScore}, isGerman: ${isGerman} (type: ${typeof isGerman}), requiresModeration: ${requiresModeration}, language: ${language}`)

    // Handle non-German comments
    // Only block/moderate if comment is explicitly not German AND requires moderation
    // Empty text or unknown language should be allowed (fail-open)
    logger.info(`[AI Mod DEBUG] Checking non-German condition: isGerman === false: ${isGerman === false}, requiresModeration: ${requiresModeration}, hasText: ${!!(text && text.trim())}`)
    
    if (isGerman === false && requiresModeration && text && text.trim()) {
      const nonGermanAction = await settingsManager.getSetting('non_german_action') || 'moderate'

      if (nonGermanAction === 'block') {
        logger.info(`[AI Mod] Blocked non-German comment (lang=${language}): ${text.substring(0, 50)}...`)
        // Save to database for moderation
        await saveBlockedComment(text, params, 'non_german', null, null, language)
        return { 
          accepted: false, 
          errorMessage: `Kommentar abgelehnt: Kommentare in der Sprache "${language}" sind nicht erlaubt. Nur Kommentare auf Deutsch sind erlaubt.` 
        }
      } else {
        // moderate: save to database for manual review
        logger.info(`[AI Mod] Non-German comment sent to moderation (lang=${language}): ${text.substring(0, 50)}...`)
        await saveBlockedComment(text, params, 'non_german', null, null, language)
        return { accepted: false, errorMessage: 'Kommentar wurde zur manuellen Überprüfung gesendet.' }
      }
    }

    // Handle toxic German comments
    if (!allowed) {
      const scoreInfo = hateScore > 0 ? `score=${score.toFixed(3)}, hate=${hateScore.toFixed(3)}` : `score=${score.toFixed(3)}`
      logger.info(`[AI Mod] Blocked toxic comment (${scoreInfo}, lang=${language}): ${text.substring(0, 50)}...`)
      
      // Save to database for moderation
      await saveBlockedComment(text, params, 'toxic', score, hateScore, language)
      
      // Create user-friendly error message in German
      let errorMsg = 'Kommentar abgelehnt: Der Kommentar enthält beleidigende Inhalte.'
      if (hateScore >= 0.5) {
        errorMsg = 'Kommentar abgelehnt: Der Kommentar enthält beleidigende Inhalte und Hassrede.'
      } else if (score >= 0.7) {
        errorMsg = 'Kommentar abgelehnt: Der Kommentar enthält beleidigende Inhalte.'
      }
      
      return { accepted: false, errorMessage: errorMsg }
    }

    logger.info(`[AI Mod DEBUG] Comment allowed, returning true`)
    return { accepted: true }
  }

  /**
   * Extract accepted boolean from hook result (supports both old and new API)
   * @param {boolean|object} acceptedOrResult - Either boolean (old API) or {accepted: boolean} (new API)
   * @returns {boolean}
   */
  function getAcceptedBool (acceptedOrResult) {
    // New API (PeerTube 7.x): { accepted: boolean, errorMessage?: string }
    if (acceptedOrResult && typeof acceptedOrResult === 'object' && typeof acceptedOrResult.accepted === 'boolean') {
      return acceptedOrResult.accepted
    }

    // Old API: just boolean
    if (typeof acceptedOrResult === 'boolean') {
      return acceptedOrResult
    }

    // Fail-open: allow by default if format is unexpected
    logger.warn('[AI Mod] Unexpected acceptedOrResult format, defaulting to true')
    return true
  }

  /**
   * Wrapper for accept hooks that handles both old and new PeerTube API
   * @param {boolean|object} acceptedOrResult - Hook result from previous handlers
   * @param {object} params - Comment parameters
   * @returns {Promise<object>} - {accepted: boolean}
   */
  async function wrapAcceptHook (acceptedOrResult, params) {
    logger.info(`[AI Mod DEBUG] wrapAcceptHook called - acceptedOrResult type: ${typeof acceptedOrResult}, value: ${JSON.stringify(acceptedOrResult)}`)
    
    const acceptedBool = getAcceptedBool(acceptedOrResult)
    logger.info(`[AI Mod DEBUG] Extracted acceptedBool: ${acceptedBool}`)
    
    const result = await handleCommentModeration(acceptedBool, params)
    
    // result is now an object: { accepted: boolean, errorMessage?: string, heldForReview?: boolean }
    logger.info(`[AI Mod DEBUG] wrapAcceptHook returning: ${JSON.stringify(result)}`)
    
    // Always return object as PeerTube 7.x expects
    // PeerTube 7.x supports: { accepted: boolean, errorMessage?: string }
    return { accepted: result.accepted, errorMessage: result.errorMessage }
  }

  // Hook for local new threads (main comments)
  registerHook({
    target: 'filter:api.video-thread.create.accept.result',
    handler: async (acceptedOrResult, params) => {
      return await wrapAcceptHook(acceptedOrResult, params)
    }
  })

  // Hook for local replies
  registerHook({
    target: 'filter:api.video-comment-reply.create.accept.result',
    handler: async (acceptedOrResult, params) => {
      return await wrapAcceptHook(acceptedOrResult, params)
    }
  })

  // Hook for remote comments from federation
  registerHook({
    target: 'filter:activity-pub.remote-video-comment.create.accept.result',
    handler: async (acceptedOrResult, params) => {
      return await wrapAcceptHook(acceptedOrResult, params)
    }
  })

  // API Routes for moderation
  if (getRouter) {
    const router = getRouter()

    // Check if user can moderate comments (root admin or video owner)
    async function checkModerationAccess (req, res, next) {
      try {
        const user = await peertubeHelpers.user.getAuthUser(res)
        if (!user) {
          return res.status(401).json({ error: 'Authentifizierung erforderlich' })
        }

        // Root admin has access to all comments
        if (user.role === 0) {
          req.user = user
          req.isRootAdmin = true
          return next()
        }

        // For non-root users, we'll filter by their videos in the routes
        req.user = user
        req.isRootAdmin = false
        next()
      } catch (err) {
        logger.error('[AI Mod] Error checking moderation access:', err)
        res.status(401).json({ error: 'Authentifizierung erforderlich' })
      }
    }

    // Get user's video IDs (for channel owners)
    async function getUserVideoIds (userId) {
      try {
        const query = 'SELECT id FROM "video" WHERE "channelId" IN (SELECT id FROM "videoChannel" WHERE "ownerId" = $1)'
        const result = await database.query(query, [userId])
        return result.rows.map(row => row.id)
      } catch (err) {
        logger.error('[AI Mod] Error getting user video IDs:', err)
        return []
      }
    }

    // Get blocked comments
    router.get('/blocked-comments', checkModerationAccess, async (req, res) => {
      try {
        const { page = 1, limit = 50, blockReason, approved, videoId } = req.query
        const offset = (parseInt(page) - 1) * parseInt(limit)

        let query = 'SELECT * FROM "pluginGermanAiModBlockedComments" WHERE 1=1'
        const params = []
        let paramIndex = 1

        // Filter by video if not root admin
        if (!req.isRootAdmin) {
          const userVideoIds = await getUserVideoIds(req.user.id)
          if (userVideoIds.length === 0) {
            // User has no videos, return empty result
            return res.json({
              data: [],
              total: 0,
              page: parseInt(page),
              limit: parseInt(limit)
            })
          }
          query += ` AND "videoId" = ANY($${paramIndex}::int[])`
          params.push(userVideoIds)
          paramIndex++
        }

        // Filter by specific video if provided
        if (videoId) {
          query += ` AND "videoId" = $${paramIndex}`
          params.push(parseInt(videoId))
          paramIndex++
        }

        if (blockReason) {
          query += ` AND "blockReason" = $${paramIndex}`
          params.push(blockReason)
          paramIndex++
        }

        if (approved !== undefined) {
          query += ` AND "approved" = $${paramIndex}`
          params.push(approved === 'true')
          paramIndex++
        }

        query += ` ORDER BY "createdAt" DESC LIMIT $${paramIndex} OFFSET $${paramIndex + 1}`
        params.push(parseInt(limit), offset)

        const result = await database.query(query, params)
        
        // Get total count with same filters
        let countQuery = 'SELECT COUNT(*) as count FROM "pluginGermanAiModBlockedComments" WHERE 1=1'
        const countParams = []
        let countParamIndex = 1

        if (!req.isRootAdmin) {
          const userVideoIds = await getUserVideoIds(req.user.id)
          if (userVideoIds.length > 0) {
            countQuery += ` AND "videoId" = ANY($${countParamIndex}::int[])`
            countParams.push(userVideoIds)
            countParamIndex++
          } else {
            countQuery += ` AND 1=0` // No videos, no comments
          }
        }

        if (videoId) {
          countQuery += ` AND "videoId" = $${countParamIndex}`
          countParams.push(parseInt(videoId))
          countParamIndex++
        }

        if (blockReason) {
          countQuery += ` AND "blockReason" = $${countParamIndex}`
          countParams.push(blockReason)
          countParamIndex++
        }

        if (approved !== undefined) {
          countQuery += ` AND "approved" = $${countParamIndex}`
          countParams.push(approved === 'true')
          countParamIndex++
        }

        const countResult = await database.query(countQuery, countParams)
        
        res.json({
          data: result.rows,
          total: parseInt(countResult.rows[0].count),
          page: parseInt(page),
          limit: parseInt(limit),
          isRootAdmin: req.isRootAdmin
        })
      } catch (err) {
        logger.error('[AI Mod] Error getting blocked comments:', err)
        res.status(500).json({ error: 'Serverfehler' })
      }
    })

    // Approve (unblock) comment
    router.post('/blocked-comments/:id/approve', checkModerationAccess, async (req, res) => {
      try {
        const { id } = req.params
        const userId = req.user.id

        // First check if user has access to this comment
        const checkQuery = 'SELECT "videoId" FROM "pluginGermanAiModBlockedComments" WHERE "id" = $1'
        const checkResult = await database.query(checkQuery, [id])

        if (checkResult.rows.length === 0) {
          return res.status(404).json({ error: 'Kommentar nicht gefunden' })
        }

        // If not root admin, verify user owns the video
        if (!req.isRootAdmin) {
          const userVideoIds = await getUserVideoIds(req.user.id)
          if (!userVideoIds.includes(checkResult.rows[0].videoId)) {
            return res.status(403).json({ error: 'Keine Berechtigung für dieses Video' })
          }
        }

        const query = `
          UPDATE "pluginGermanAiModBlockedComments"
          SET "approved" = true, "approvedBy" = $1, "approvedAt" = NOW()
          WHERE "id" = $2
          RETURNING *
        `
        const result = await database.query(query, [req.user.id, id])

        logger.info(`[AI Mod] Comment ${id} approved by user ${req.user.username}`)
        res.json({ success: true, comment: result.rows[0] })
      } catch (err) {
        logger.error('[AI Mod] Error approving comment:', err)
        res.status(500).json({ error: 'Serverfehler' })
      }
    })

    // Delete blocked comment
    router.delete('/blocked-comments/:id', checkModerationAccess, async (req, res) => {
      try {
        const { id } = req.params

        // First check if user has access to this comment
        const checkQuery = 'SELECT "videoId" FROM "pluginGermanAiModBlockedComments" WHERE "id" = $1'
        const checkResult = await database.query(checkQuery, [id])

        if (checkResult.rows.length === 0) {
          return res.status(404).json({ error: 'Kommentar nicht gefunden' })
        }

        // If not root admin, verify user owns the video
        if (!req.isRootAdmin) {
          const userVideoIds = await getUserVideoIds(req.user.id)
          if (!userVideoIds.includes(checkResult.rows[0].videoId)) {
            return res.status(403).json({ error: 'Keine Berechtigung für dieses Video' })
          }
        }

        const query = 'DELETE FROM "pluginGermanAiModBlockedComments" WHERE "id" = $1'
        await database.query(query, [id])
        logger.info(`[AI Mod] Comment ${id} deleted by user ${req.user.username}`)
        res.json({ success: true })
      } catch (err) {
        logger.error('[AI Mod] Error deleting comment:', err)
        res.status(500).json({ error: 'Serverfehler' })
      }
    })

    // Get user's videos for filtering
    router.get('/my-videos', checkModerationAccess, async (req, res) => {
      try {
        if (req.isRootAdmin) {
          // Root admin sees all videos
          const query = 'SELECT id, uuid, name FROM "video" ORDER BY "createdAt" DESC LIMIT 1000'
          const result = await database.query(query)
          res.json({ videos: result.rows })
        } else {
          // Channel owners see only their videos
          const userVideoIds = await getUserVideoIds(req.user.id)
          if (userVideoIds.length === 0) {
            return res.json({ videos: [] })
          }
          const query = 'SELECT id, uuid, name FROM "video" WHERE id = ANY($1::int[]) ORDER BY "createdAt" DESC'
          const result = await database.query(query, [userVideoIds])
          res.json({ videos: result.rows })
        }
      } catch (err) {
        logger.error('[AI Mod] Error getting videos:', err)
        res.status(500).json({ error: 'Serverfehler' })
      }
    })
  }
}

module.exports = {
  register,
  unregister () {}
}

