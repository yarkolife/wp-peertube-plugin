# PeerTube Video Manager

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)

Ein WordPress-Plugin zur Integration von PeerTube-Videos mit Shortcodes. Zeigt Videos, Kanäle, Suche und benutzerdefinierte Metadaten einschließlich Sendeverantwortung an.

**Repository:** [https://github.com/yarkolife/wp-peertube-plugin](https://github.com/yarkolife/wp-peertube-plugin)

## Funktionen

- **4 Shortcodes** für verschiedene Video-Ansichten
- **Intelligente Caching** für optimale Performance
- **Responsive Design** für alle Bildschirmgrößen
- **Benutzerdefinierte Metadaten** aus PeerTube-Plugins
- **Suche** mit Paginierung
- **Konfigurierbares Backend** mit Einstellungsseite
- **Deutsche Übersetzung** included

## Anforderungen

- WordPress 6.0 oder höher
- PHP 7.4 oder höher
- PeerTube Instanz (API-Zugriff)

## Installation

### Über WordPress Admin

1. Laden Sie die [ZIP-Datei](https://github.com/yarkolife/wp-peertube-plugin/releases/latest) herunter
2. Gehen Sie zu `Plugins > Installieren > Plugin hochladen`
3. Wählen Sie die ZIP-Datei aus
4. Klicken Sie auf "Jetzt installieren"
5. Aktivieren Sie das Plugin

### Über Git (für Entwickler)

```bash
cd wp-content/plugins/
git clone https://github.com/yarkolife/wp-peertube-plugin.git peertube-video-manager
```

### Manuell

1. Laden Sie die Plugin-Dateien in `/wp-content/plugins/peertube-video-manager/` hoch
2. Aktivieren Sie das Plugin über das 'Plugins'-Menü in WordPress

## Konfiguration

Gehen Sie nach der Aktivierung zu `Einstellungen > PeerTube Videos`:

- **PeerTube Instanz URL**: Die URL Ihrer PeerTube-Instanz (z.B. `https://lokalmedial.de`)
- **Standard-Kanäle**: Liste der Kanal-Handles (einen pro Zeile)
- **Cache-Zeiten**: Wie lange Daten zwischengespeichert werden
- **Videos pro Seite**: Standard-Anzahl der angezeigten Videos

## Shortcodes

### [pt-last-videos]

Zeigt die neuesten Videos der Instanz an.

**Attribute:**
- `count` (optional, Standard: 8) - Anzahl der Videos
- `host_only` (optional, Standard: "true") - Nur lokale Videos

**Beispiele:**
```
[pt-last-videos]
[pt-last-videos count="12"]
[pt-last-videos count="6" host_only="false"]
```

### [pt-latest-per-channel]

Zeigt das neueste Video von jedem Kanal an.

**Attribute:**
- `channels` (optional) - Komma-getrennte Liste von Kanal-Handles

**Beispiele:**
```
[pt-latest-per-channel]
[pt-latest-per-channel channels="ok_dessau,ok_magdeburg,okmq"]
```

Wenn kein `channels`-Attribut angegeben wird, verwendet das Plugin die Standard-Kanäle aus den Einstellungen.

### [pt-channel-videos]

Zeigt Videos eines bestimmten Kanals an.

**Attribute:**
- `channel` (erforderlich) - Kanal-Handle
- `count` (optional, Standard: 6) - Anzahl der Videos

**Beispiele:**
```
[pt-channel-videos channel="okmq"]
[pt-channel-videos channel="ok_dessau" count="10"]
```

### [pt-video]

Zeigt ein einzelnes Video mit allen Details an.

**Attribute:**
- `id` - Video UUID oder shortUUID
- `number` - Video-Nummer (aus Plugin-Daten)

**Beispiele:**
```
[pt-video id="xc86cB87iZXsgCofjHVcYJ"]
[pt-video number="12345"]
```

**Hinweis:** Entweder `id` oder `number` muss angegeben werden.

### [pt-search]

Zeigt ein Suchformular an.

**Attribute:**
- `placeholder` (optional) - Platzhalter-Text
- `action` (optional) - Ziel-URL für Suchergebnisse

**Beispiele:**
```
[pt-search]
[pt-search placeholder="Videos suchen..."]
[pt-search action="/suchergebnisse/"]
```

### [pt-search-results]

Zeigt Suchergebnisse mit Paginierung an.

**Attribute:**
- `per_page` (optional, Standard: 12) - Videos pro Seite

**Beispiele:**
```
[pt-search-results]
[pt-search-results per_page="20"]
```

## Angezeigte Metadaten

Für jedes Video werden folgende Informationen angezeigt:

- **Thumbnail** - Video-Vorschaubild
- **Titel** - Video-Name
- **Länge** - Dauer des Videos (⏱)
- **Kategorie** - Video-Kategorie (🏷)
- **Veröffentlichungsdatum** - Relatives Datum (📅)
- **Aufrufe** - Anzahl der Ansichten (👁)
- **Sendeverantwortung** - Aus PeerTube-Plugin (👤)
- **Video-Nummer** - Aus PeerTube-Plugin (🔢)
- **Tags** - Bis zu 5 Tags pro Video

## Caching

Das Plugin verwendet WordPress Transients für Caching:

- **Video-Listen**: 5 Minuten (konfigurierbar)
- **Konfiguration**: 24 Stunden (konfigurierbar)
- **Einzelne Videos**: 10 Minuten
- **Suchergebnisse**: 2 Minuten

Um den Cache zu leeren, gehen Sie zu `Einstellungen > PeerTube Videos` und klicken Sie auf "Cache löschen".

## Performance

- Respektiert PeerTube API Rate-Limits (50 Anfragen/10 Sekunden)
- Intelligentes Caching reduziert API-Aufrufe
- Lazy Loading für Bilder
- Responsive CSS Grid für optimale Darstellung

## Anpassungen

### CSS anpassen

Sie können die Stile überschreiben, indem Sie eigene CSS-Regeln in Ihrem Theme hinzufügen:

```css
/* Beispiel: Video-Karten anpassen */
.pt-video-card {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
```

### Templates überschreiben

Kopieren Sie die Template-Dateien aus `templates/` in Ihr Theme-Verzeichnis:

```
your-theme/peertube-video-manager/video-card.php
your-theme/peertube-video-manager/video-detail.php
your-theme/peertube-video-manager/search-form.php
```

## Häufige Probleme

### Keine Videos werden angezeigt

1. Überprüfen Sie die PeerTube-URL in den Einstellungen
2. Klicken Sie auf "Verbindung testen"
3. Leeren Sie den Cache
4. Überprüfen Sie die Browser-Konsole auf Fehler

### Videos werden nicht aktualisiert

Leeren Sie den Cache über `Einstellungen > PeerTube Videos > Cache löschen`.

### 404-Fehler bei Video-URLs

Stellen Sie sicher, dass die PeerTube-URL korrekt ist und die Videos öffentlich zugänglich sind.

### Langsame Ladezeiten

- Reduzieren Sie die Anzahl der Videos pro Seite
- Erhöhen Sie die Cache-Zeit
- Überprüfen Sie die Verbindung zur PeerTube-Instanz

## Entwicklung

### Struktur

```
peertube-video-manager/
├── peertube-video-manager.php    # Hauptdatei
├── includes/                      # Core-Klassen
├── shortcodes/                    # Shortcode-Klassen
├── templates/                     # Template-Dateien
├── assets/                        # CSS & JS
└── languages/                     # Übersetzungen
```

### Hooks & Filter

Das Plugin bietet verschiedene Hooks für Entwickler:

```php
// Filter für Video-Daten vor dem Rendering
add_filter('pt_vm_video_data', function($video) {
    // Modify video data
    return $video;
});

// Action nach dem Leeren des Caches
add_action('pt_vm_cache_cleared', function() {
    // Do something
});
```

## Changelog

### Version 1.0.0
- Erste Veröffentlichung
- 4 Shortcodes implementiert
- Caching-System
- Admin-Einstellungsseite
- Deutsche Übersetzung

## Support

Bei Fragen oder Problemen:

1. Überprüfen Sie die [Dokumentation](docs/USAGE_DE.md)
2. Aktivieren Sie WP_DEBUG für detaillierte Fehler
3. Erstellen Sie ein [Issue auf GitHub](https://github.com/yarkolife/wp-peertube-plugin/issues)

## Beitragen

Beiträge sind willkommen! Bitte:

1. Forken Sie das [Repository](https://github.com/yarkolife/wp-peertube-plugin)
2. Erstellen Sie einen Feature-Branch (`git checkout -b feature/AmazingFeature`)
3. Committen Sie Ihre Änderungen (`git commit -m 'Add some AmazingFeature'`)
4. Pushen Sie zum Branch (`git push origin feature/AmazingFeature`)
5. Öffnen Sie einen [Pull Request](https://github.com/yarkolife/wp-peertube-plugin/pulls)

## Lizenz

GPL v2 oder höher

## Credits

Entwickelt für die Integration von PeerTube-Videos in WordPress. Unterstützt benutzerdefinierte Plugin-Daten aus `peertube-plugin-okas-dev`.

## Technische Details

### API-Endpunkte

Das Plugin verwendet folgende PeerTube API-Endpunkte:

- `GET /api/v1/videos` - Liste aller Videos
- `GET /api/v1/videos/{id}` - Einzelnes Video
- `GET /api/v1/video-channels/{handle}/videos` - Kanal-Videos
- `GET /api/v1/search/videos` - Video-Suche
- `GET /api/v1/config` - Instanz-Konfiguration

### Sicherheit

- Alle Benutzereingaben werden sanitisiert
- Ausgaben werden escaped
- Nonces für AJAX-Anfragen
- Capability-Checks für Admin-Funktionen
- CORS-konforme API-Anfragen

### Browser-Kompatibilität

- Chrome/Edge (Chromium) ✓
- Firefox ✓
- Safari ✓
- Mobile Browser ✓

## Mitwirken

Beiträge sind willkommen! Bitte:

1. Forken Sie das Repository
2. Erstellen Sie einen Feature-Branch
3. Committen Sie Ihre Änderungen
4. Pushen Sie zum Branch
5. Erstellen Sie einen Pull Request

## Autor

Entwickelt mit ❤️ für die PeerTube-Community

