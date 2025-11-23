async function register ({ registerClientRoute, registerHook, peertubeHelpers }) {
  const { notifier } = peertubeHelpers

  // Register admin route for comment moderation
  registerClientRoute({
    route: '/admin/plugins/german-ai-mod/moderation',
    onMount: async ({ rootEl }) => {
      const authHeader = peertubeHelpers.getAuthHeader()

      // Check if user is authenticated
      try {
        const user = await peertubeHelpers.user.getAuthUser()
        if (!user) {
          rootEl.innerHTML = `
            <div class="margin-content col-md-12 col-xl-8" style="padding-top: 30px;">
              <div style="text-align: center; padding: 40px;">
                <h2>Zugriff verweigert</h2>
                <p>Bitte melden Sie sich an, um Kommentare zu moderieren.</p>
              </div>
            </div>
          `
          return
        }
      } catch (err) {
        rootEl.innerHTML = `
          <div class="margin-content col-md-12 col-xl-8" style="padding-top: 30px;">
            <div style="text-align: center; padding: 40px;">
              <h2>Fehler</h2>
              <p>Fehler bei der Authentifizierung: ${err.message}</p>
            </div>
          </div>
        `
        return
      }

      // Load moderation interface
      loadModerationInterface(rootEl, authHeader)
    }
  })

  async function loadModerationInterface (rootEl, authHeader) {
    // Load videos for filtering
    let videos = []
    let isRootAdmin = false
    try {
      const videosResponse = await fetch('/plugins/german-ai-mod/router/my-videos', {
        headers: authHeader || {}
      })
      if (videosResponse.ok) {
        const videosData = await videosResponse.json()
        videos = videosData.videos || []
      }
      
      // Check if user is root admin
      const commentsResponse = await fetch('/plugins/german-ai-mod/router/blocked-comments?page=1&limit=1', {
        headers: authHeader || {}
      })
      if (commentsResponse.ok) {
        const commentsData = await commentsResponse.json()
        isRootAdmin = commentsData.isRootAdmin || false
      }
    } catch (err) {
      console.error('Error loading videos:', err)
    }

    rootEl.innerHTML = `
      <div class="margin-content col-md-12 col-xl-10" style="padding-top: 30px;">
        <h1>AI-Moderation: Blockierte Kommentare</h1>
        ${!isRootAdmin ? '<p style="color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin-bottom: 20px;">Sie sehen nur Kommentare zu Ihren eigenen Videos.</p>' : ''}
        <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">
          <div>
            <strong>Filter:</strong>
            <label style="margin-left: 10px;">
              <input type="radio" name="filter" value="all" checked> Alle
            </label>
            <label style="margin-left: 10px;">
              <input type="radio" name="filter" value="toxic"> Toxisch
            </label>
            <label style="margin-left: 10px;">
              <input type="radio" name="filter" value="non_german"> Nicht-Deutsch
            </label>
            <label style="margin-left: 10px;">
              <input type="radio" name="filter" value="pending"> Ausstehend
            </label>
            <label style="margin-left: 10px;">
              <input type="radio" name="filter" value="approved"> Genehmigt
            </label>
          </div>
          ${videos.length > 0 ? `
            <div style="margin-left: auto;">
              <label>
                <strong>Video:</strong>
                <select id="video-filter" style="margin-left: 10px; padding: 5px;">
                  <option value="">Alle Videos</option>
                  ${videos.map(v => `<option value="${v.id}">${escapeHtml(v.name || `Video ${v.id}`)}</option>`).join('')}
                </select>
              </label>
            </div>
          ` : ''}
        </div>
        <div id="comments-list" style="margin-top: 20px;">
          <p>Lade Kommentare...</p>
        </div>
      </div>
    `

    const filterRadios = rootEl.querySelectorAll('input[name="filter"]')
    filterRadios.forEach(radio => {
      radio.addEventListener('change', () => {
        const videoFilter = rootEl.querySelector('#video-filter')
        loadComments(rootEl, authHeader, radio.value, videoFilter ? videoFilter.value : '')
      })
    })

    const videoFilter = rootEl.querySelector('#video-filter')
    if (videoFilter) {
      videoFilter.addEventListener('change', () => {
        const selectedFilter = rootEl.querySelector('input[name="filter"]:checked')
        loadComments(rootEl, authHeader, selectedFilter ? selectedFilter.value : 'all', videoFilter.value)
      })
    }

    loadComments(rootEl, authHeader, 'all', '')
  }

  async function loadComments (rootEl, authHeader, filter, videoId = '') {
    const listEl = rootEl.querySelector('#comments-list')
    listEl.innerHTML = '<p>Lade Kommentare...</p>'

    try {
      let url = '/plugins/german-ai-mod/router/blocked-comments?page=1&limit=100'
      if (filter === 'toxic' || filter === 'non_german') {
        url += `&blockReason=${filter}`
      } else if (filter === 'pending') {
        url += '&approved=false'
      } else if (filter === 'approved') {
        url += '&approved=true'
      }
      if (videoId) {
        url += `&videoId=${videoId}`
      }

      const response = await fetch(url, {
        headers: authHeader || {}
      })

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`)
      }

      const data = await response.json()

      if (data.data.length === 0) {
        listEl.innerHTML = '<p>Keine blockierten Kommentare gefunden.</p>'
        return
      }

      listEl.innerHTML = `
        <table style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="border-bottom: 2px solid #ddd;">
              <th style="padding: 10px; text-align: left;">Text</th>
              <th style="padding: 10px; text-align: left;">Video</th>
              <th style="padding: 10px; text-align: left;">Grund</th>
              <th style="padding: 10px; text-align: left;">Sprache</th>
              <th style="padding: 10px; text-align: left;">Scores</th>
              <th style="padding: 10px; text-align: left;">Benutzer</th>
              <th style="padding: 10px; text-align: left;">Datum</th>
              <th style="padding: 10px; text-align: left;">Aktionen</th>
            </tr>
          </thead>
          <tbody id="comments-tbody">
          </tbody>
        </table>
      `

      const tbody = listEl.querySelector('#comments-tbody')
      data.data.forEach(comment => {
        const row = document.createElement('tr')
        row.style.borderBottom = '1px solid #eee'
        
        const reasonBadge = comment.blockReason === 'toxic' 
          ? '<span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.85em;">Toxisch</span>'
          : '<span style="background: #ffc107; color: black; padding: 2px 8px; border-radius: 3px; font-size: 0.85em;">Nicht-Deutsch</span>'
        
        const scoreText = comment.score !== null 
          ? `Tox: ${comment.score.toFixed(3)}${comment.hateScore !== null ? `, Hate: ${comment.hateScore.toFixed(3)}` : ''}`
          : '-'
        
        const approvedBadge = comment.approved 
          ? '<span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.85em;">Genehmigt</span>'
          : '<span style="background: #6c757d; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.85em;">Ausstehend</span>'

        const videoName = comment.videoUuid 
          ? `<a href="/videos/watch/${comment.videoUuid}" target="_blank" style="color: #007bff; text-decoration: none;">Video ${comment.videoId || comment.videoUuid}</a>`
          : `Video ${comment.videoId || '-'}`

        row.innerHTML = `
          <td style="padding: 10px; max-width: 300px;">
            <div style="max-height: 60px; overflow: hidden; text-overflow: ellipsis;">
              ${escapeHtml(comment.text)}
            </div>
          </td>
          <td style="padding: 10px; font-size: 0.9em;">${videoName}</td>
          <td style="padding: 10px;">${reasonBadge}</td>
          <td style="padding: 10px;">${comment.language || '-'}</td>
          <td style="padding: 10px; font-size: 0.9em;">${scoreText}</td>
          <td style="padding: 10px;">${comment.userUsername || '-'}</td>
          <td style="padding: 10px; font-size: 0.9em;">${new Date(comment.createdAt).toLocaleString('de-DE')}</td>
          <td style="padding: 10px;">
            ${comment.approved ? '' : `<button class="btn btn-success btn-sm" onclick="approveComment(${comment.id}, this)" style="margin-right: 5px;">Genehmigen</button>`}
            <button class="btn btn-danger btn-sm" onclick="deleteComment(${comment.id}, this)">Löschen</button>
            ${approvedBadge}
          </td>
        `
        tbody.appendChild(row)
      })

      // Add global functions for buttons
      window.approveComment = async function (id, buttonEl) {
        if (!confirm('Kommentar wirklich genehmigen? Er wird dann veröffentlicht.')) {
          return
        }

        buttonEl.disabled = true
        buttonEl.textContent = 'Wird genehmigt...'

        try {
          const response = await fetch(`/plugins/german-ai-mod/router/blocked-comments/${id}/approve`, {
            method: 'POST',
            headers: authHeader || {}
          })

          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`)
          }

          notifier.success('Kommentar wurde genehmigt')
          const selectedFilter = rootEl.querySelector('input[name="filter"]:checked')
          const videoFilter = rootEl.querySelector('#video-filter')
          loadComments(rootEl, authHeader, selectedFilter ? selectedFilter.value : 'all', videoFilter ? videoFilter.value : '')
        } catch (err) {
          notifier.error('Fehler beim Genehmigen: ' + err.message)
          buttonEl.disabled = false
          buttonEl.textContent = 'Genehmigen'
        }
      }

      window.deleteComment = async function (id, buttonEl) {
        if (!confirm('Kommentar wirklich löschen?')) {
          return
        }

        buttonEl.disabled = true
        buttonEl.textContent = 'Wird gelöscht...'

        try {
          const response = await fetch(`/plugins/german-ai-mod/router/blocked-comments/${id}`, {
            method: 'DELETE',
            headers: authHeader || {}
          })

          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`)
          }

          notifier.success('Kommentar wurde gelöscht')
          const selectedFilter = rootEl.querySelector('input[name="filter"]:checked')
          const videoFilter = rootEl.querySelector('#video-filter')
          loadComments(rootEl, authHeader, selectedFilter ? selectedFilter.value : 'all', videoFilter ? videoFilter.value : '')
        } catch (err) {
          notifier.error('Fehler beim Löschen: ' + err.message)
          buttonEl.disabled = false
          buttonEl.textContent = 'Löschen'
        }
      }

    } catch (err) {
      listEl.innerHTML = `<p style="color: red;">Fehler beim Laden: ${err.message}</p>`
    }
  }

  function escapeHtml (text) {
    const div = document.createElement('div')
    div.textContent = text
    return div.innerHTML
  }
}

module.exports = {
  register,
  unregister () {}
}


