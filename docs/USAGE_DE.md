# PeerTube Video Manager - Benutzerhandbuch

## Inhaltsverzeichnis

1. [Einrichtung](#einrichtung)
2. [Shortcodes erklärt](#shortcodes-erklärt)
3. [Häufige Probleme](#häufige-probleme)
4. [Tipps zur Leistungsoptimierung](#tipps-zur-leistungsoptimierung)

## Einrichtung

### Erstinstallation

1. **Plugin installieren und aktivieren**
   - Laden Sie die Plugin-ZIP-Datei herunter
   - Gehen Sie zu `Plugins > Installieren > Plugin hochladen`
   - Wählen Sie die ZIP-Datei und klicken Sie auf "Jetzt installieren"
   - Aktivieren Sie das Plugin nach der Installation

2. **Grundeinstellungen konfigurieren**
   - Gehen Sie zu `Einstellungen > PeerTube Videos`
   - Geben Sie die URL Ihrer PeerTube-Instanz ein (z.B. `https://lokalmedial.de`)
   - Klicken Sie auf "Verbindung testen" um die Verbindung zu überprüfen
   - Speichern Sie die Einstellungen

3. **Optional: Standard-Kanäle einrichten**
   - Geben Sie Kanal-Handles in das Textfeld "Standard-Kanäle" ein
   - Ein Kanal-Handle pro Zeile (z.B. `ok_dessau`, `ok_magdeburg`)
   - Diese werden für `[pt-latest-per-channel]` verwendet, wenn keine Kanäle angegeben sind

### Konfigurationsoptionen

#### PeerTube Instanz URL
Die vollständige URL Ihrer PeerTube-Instanz ohne abschließenden Schrägstrich.

**Beispiel:** `https://lokalmedial.de`

#### Standard-Kanäle
Liste der Kanal-Handles, die standardmäßig verwendet werden. Geben Sie jeden Kanal in eine neue Zeile ein.

**Beispiel:**
```
ok_dessau
ok_magdeburg
okmq
```

#### Cache-Zeit für Videos
Wie lange Video-Listen im Cache bleiben (in Minuten). Standard: 5 Minuten.

**Empfehlung:** 
- 5 Minuten für häufig aktualisierte Inhalte
- 15-30 Minuten für statischere Inhalte

#### Cache-Zeit für Konfiguration
Wie lange Kategorien und Konfiguration gecacht werden (in Stunden). Standard: 24 Stunden.

#### Videos pro Seite
Standard-Anzahl der Videos, die ohne explizite Angabe angezeigt werden. Standard: 8.

## Shortcodes erklärt

### [pt-last-videos] - Neueste Videos

Zeigt die neuesten Videos Ihrer PeerTube-Instanz in einem responsiven Grid an.

#### Verwendung

```
[pt-last-videos]
```

#### Parameter

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `count` | Zahl | 8 | Anzahl der anzuzeigenden Videos |
| `host_only` | true/false | true | Nur lokale Videos (keine föderierten) |

#### Beispiele

**Standardansicht mit 8 Videos:**
```
[pt-last-videos]
```

**12 Videos anzeigen:**
```
[pt-last-videos count="12"]
```

**Alle Videos inklusive föderierter Videos:**
```
[pt-last-videos count="10" host_only="false"]
```

#### Was wird angezeigt?

- Video-Thumbnail mit Dauer-Overlay
- Video-Titel (klickbar)
- Länge, Kategorie, Veröffentlichungsdatum, Aufrufe
- Sendeverantwortung (falls vorhanden)
- Video-Nummer (falls vorhanden)
- Tags (bis zu 5)

---

### [pt-latest-per-channel] - Neueste Videos pro Kanal

Zeigt das neueste Video von jedem angegebenen Kanal an. Ideal für Übersichtsseiten.

#### Verwendung

```
[pt-latest-per-channel]
```

oder

```
[pt-latest-per-channel channels="kanal1,kanal2,kanal3"]
```

#### Parameter

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `channels` | Text | (aus Einstellungen) | Komma-getrennte Liste von Kanal-Handles |

#### Beispiele

**Verwendet Standard-Kanäle aus Einstellungen:**
```
[pt-latest-per-channel]
```

**Spezifische Kanäle:**
```
[pt-latest-per-channel channels="ok_dessau,ok_magdeburg,okmq"]
```

#### Hinweise

- Wenn kein `channels`-Parameter angegeben ist, werden die Standard-Kanäle aus den Plugin-Einstellungen verwendet
- Videos werden nach Veröffentlichungsdatum sortiert (neueste zuerst)
- Jeder Kanal wird unabhängig gecacht für bessere Performance

---

### [pt-channel-videos] - Alle Videos eines Kanals

Zeigt mehrere Videos eines bestimmten Kanals an.

#### Verwendung

```
[pt-channel-videos channel="kanal_handle"]
```

#### Parameter

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `channel` | Text | (erforderlich) | Kanal-Handle |
| `count` | Zahl | 6 | Anzahl der Videos |

#### Beispiele

**6 neueste Videos von OK MQ:**
```
[pt-channel-videos channel="okmq"]
```

**10 Videos von OK Dessau:**
```
[pt-channel-videos channel="ok_dessau" count="10"]
```

#### Fehlermeldungen

Wenn der Kanal nicht gefunden wird oder keine Videos hat, wird eine entsprechende Meldung angezeigt.

---

### [pt-video] - Einzelnes Video mit Details

Zeigt ein einzelnes Video mit vollständiger Beschreibung und eingebettetem Player an.

#### Verwendung

**Per Video-ID:**
```
[pt-video id="UUID"]
```

**Per Video-Nummer:**
```
[pt-video number="12345"]
```

#### Parameter

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `id` | Text | - | Video UUID oder shortUUID |
| `number` | Text | - | Video-Nummer aus Plugin-Daten |

**Wichtig:** Entweder `id` oder `number` muss angegeben werden!

#### Beispiele

**Video per ID anzeigen:**
```
[pt-video id="xc86cB87iZXsgCofjHVcYJ"]
```

**Video per Video-Nummer anzeigen:**
```
[pt-video number="12345"]
```

#### Was wird angezeigt?

- Eingebetteter PeerTube-Player (16:9)
- Video-Titel
- Vollständige Metadaten:
  - Länge
  - Kategorie
  - Veröffentlichungsdatum
  - Aufrufe
  - Sendeverantwortung
  - Video-Nummer
- Alle Tags
- Vollständige Beschreibung (HTML-formatiert)
- Link "Auf PeerTube ansehen"

---

### [pt-search] - Suchformular

Zeigt ein Suchformular für PeerTube-Videos an.

#### Verwendung

```
[pt-search]
```

#### Parameter

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `placeholder` | Text | "Suche in der Mediathek" | Platzhalter-Text im Suchfeld |
| `action` | URL | (aktuelle Seite) | Ziel-URL für die Suche |

#### Beispiele

**Einfaches Suchformular:**
```
[pt-search]
```

**Mit benutzerdefiniertem Platzhalter:**
```
[pt-search placeholder="Videos durchsuchen..."]
```

**Mit spezifischer Zielseite:**
```
[pt-search action="/suchergebnisse/"]
```

---

### [pt-search-results] - Suchergebnisse

Zeigt die Suchergebnisse mit Paginierung an. Sollte auf der gleichen oder einer verlinkten Seite wie `[pt-search]` sein.

#### Verwendung

```
[pt-search-results]
```

#### Parameter

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `per_page` | Zahl | 12 | Ergebnisse pro Seite |

#### Beispiele

**Standard-Ansicht (12 Videos):**
```
[pt-search-results]
```

**20 Videos pro Seite:**
```
[pt-search-results per_page="20"]
```

#### Setup-Beispiel

**Seite 1: "Suche" (URL: /suche/)**
```
[pt-search]
[pt-search-results]
```

**Seite 2: "Suchergebnisse" (URL: /suchergebnisse/)**
```
[pt-search action="/suchergebnisse/"]
```

Dann auf `/suchergebnisse/`:
```
[pt-search-results per_page="15"]
```

## Häufige Probleme

### Problem: Keine Videos werden angezeigt

**Lösung 1: URL überprüfen**
1. Gehen Sie zu `Einstellungen > PeerTube Videos`
2. Überprüfen Sie die PeerTube Instanz URL
3. Klicken Sie auf "Verbindung testen"
4. Speichern Sie die Einstellungen neu

**Lösung 2: Cache leeren**
1. Gehen Sie zu `Einstellungen > PeerTube Videos`
2. Klicken Sie auf "Cache löschen"
3. Aktualisieren Sie die Seite mit den Videos

**Lösung 3: Kanal-Handle überprüfen**
- Stellen Sie sicher, dass der Kanal-Handle korrekt geschrieben ist
- Kanal-Handles sind case-sensitive!

### Problem: Videos werden nicht aktualisiert

Das liegt am Caching-System. Um sofort neue Videos zu sehen:

1. Gehen Sie zu `Einstellungen > PeerTube Videos`
2. Klicken Sie auf "Cache löschen"
3. Aktualisieren Sie die Seite

**Oder:** Reduzieren Sie die Cache-Zeit in den Einstellungen.

### Problem: "Video nicht gefunden" Fehler

**Bei [pt-video id="..."]:**
- Überprüfen Sie, ob die Video-ID korrekt ist
- Stellen Sie sicher, dass das Video öffentlich ist
- Überprüfen Sie, ob das Video auf der angegebenen Instanz existiert

**Bei [pt-video number="..."]:**
- Überprüfen Sie, ob das Video eine Video-Nummer hat
- Das Plugin durchsucht bis zu 500 Videos nach der Nummer
- Bei mehr Videos könnte die Suche fehlschlagen

### Problem: Suche funktioniert nicht

1. Überprüfen Sie, ob beide Shortcodes vorhanden sind:
   - `[pt-search]` für das Formular
   - `[pt-search-results]` für die Ergebnisse
2. Stellen Sie sicher, dass die Suche auf der PeerTube-Instanz aktiviert ist
3. Leeren Sie den Cache

### Problem: Langsame Ladezeiten

**Kurzfristige Lösungen:**
- Cache-Zeit erhöhen
- Anzahl der Videos pro Seite reduzieren

**Langfristige Lösungen:**
- Überprüfen Sie die Verbindungsgeschwindigkeit zur PeerTube-Instanz
- Verwenden Sie ein CDN für Ihre WordPress-Seite
- Aktivieren Sie Page-Caching auf WordPress-Ebene

## Tipps zur Leistungsoptimierung

### 1. Optimale Cache-Einstellungen

**Für häufig aktualisierte Inhalte:**
```
Videos: 5 Minuten
Konfiguration: 24 Stunden
```

**Für seltener aktualisierte Inhalte:**
```
Videos: 15-30 Minuten
Konfiguration: 48 Stunden
```

### 2. Sinnvolle Anzahl von Videos

- **Homepage:** 4-8 Videos
- **Archiv-Seiten:** 12-16 Videos
- **Kanal-Seiten:** 6-12 Videos

Zu viele Videos auf einer Seite können die Ladezeit erhöhen!

### 3. Verwendung von host_only

Wenn Sie nur Videos Ihrer eigenen Instanz anzeigen möchten:
```
[pt-last-videos host_only="true"]
```

Dies reduziert die Menge an zu verarbeitenden Daten.

### 4. Page-Caching verwenden

Verwenden Sie ein Caching-Plugin wie:
- WP Super Cache
- W3 Total Cache
- WP Rocket

Diese cachen die gesamte Seite und reduzieren die Last erheblich.

### 5. Bildoptimierung

Die Thumbnails von PeerTube werden automatisch geladen. Aktivieren Sie in Ihrem Theme:
- Lazy Loading (standardmäßig aktiviert im Plugin)
- WebP-Unterstützung im Browser

### 6. Regelmäßige Cache-Bereinigung

Planen Sie eine regelmäßige Cache-Bereinigung:
- Täglich für aktive Seiten
- Wöchentlich für Archive

Dies können Sie mit einem Cron-Job automatisieren.

### 7. Überwachung der API-Limits

PeerTube erlaubt standardmäßig:
- 50 Anfragen pro 10 Sekunden

Das Plugin respektiert diese Limits automatisch durch Caching.

## Best Practices

### Seitenstruktur

**Empfohlene Struktur:**

1. **Homepage:** Neueste Videos aller Kanäle
   ```
   [pt-latest-per-channel]
   ```

2. **Mediathek-Seite:** Alle Videos mit Suche
   ```
   [pt-search]
   [pt-last-videos count="16"]
   ```

3. **Kanal-Seiten:** Dedizierte Seite pro Kanal
   ```
   [pt-channel-videos channel="ok_dessau" count="12"]
   ```

4. **Video-Detail-Seiten:** Dynamische Seiten oder Posts
   ```
   [pt-video id="VIDEO_ID"]
   ```

### SEO-Tipps

- Verwenden Sie beschreibende Seitentitel
- Fügen Sie Meta-Beschreibungen hinzu
- Nutzen Sie die Video-Titel für H1-Überschriften
- Erstellen Sie eine XML-Sitemap mit allen Video-Seiten

### Zugänglichkeit

Das Plugin ist barrierefrei gestaltet:
- Semantisches HTML
- Alt-Texte für Bilder
- ARIA-Labels wo nötig
- Keyboard-Navigation möglich

## Erweiterte Anpassungen

### CSS-Anpassungen

Fügen Sie in Ihrem Theme eigene Styles hinzu:

```css
/* Eigene Farben */
.pt-video-card:hover {
    border-color: #yourcolor;
}

/* Andere Grid-Layout */
.pt-video-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

/* Custom Button-Style */
.pt-button-primary {
    background: #yourcolor;
    border-color: #yourcolor;
}
```

### Template-Überschreibungen

Kopieren Sie Templates in Ihr Theme:

```
wp-content/themes/ihr-theme/peertube-video-manager/
├── video-card.php
├── video-detail.php
└── search-form.php
```

Dann können Sie sie nach Belieben anpassen.

## Support und Updates

### Updates erhalten

Das Plugin prüft automatisch auf Updates. Aktivieren Sie automatische Updates in WordPress für nahtlose Aktualisierungen.

### Hilfe erhalten

1. Lesen Sie diese Dokumentation
2. Überprüfen Sie die FAQ im README
3. Aktivieren Sie WP_DEBUG für detaillierte Fehlermeldungen
4. Erstellen Sie ein Issue auf GitHub

### Debug-Modus aktivieren

In `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Fehler werden dann in `/wp-content/debug.log` protokolliert.

## Zusammenfassung

Das PeerTube Video Manager Plugin bietet eine einfache und leistungsstarke Möglichkeit, PeerTube-Videos in WordPress zu integrieren. Mit den richtigen Einstellungen und Optimierungen erhalten Sie eine schnelle, zuverlässige Video-Plattform für Ihre Website.

**Viel Erfolg mit Ihrem Video-Portal!** 🎥

