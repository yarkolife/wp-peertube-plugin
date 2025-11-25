# Beispiele für PeerTube Video Manager

Hier finden Sie praktische Beispiele für die Verwendung des Plugins in verschiedenen Szenarien.

## 1. Einfache Video-Mediathek

Erstellen Sie eine Seite "Videos" mit allen neuesten Videos.

### Setup
1. Erstellen Sie eine neue Seite: "Videos"
2. Fügen Sie diesen Shortcode ein:

```
[pt-last-videos count="16"]
```

**Ergebnis:** Eine Grid-Ansicht mit den 16 neuesten Videos Ihrer PeerTube-Instanz.

---

## 2. Kanal-Übersichtsseite

Zeigen Sie das neueste Video von jedem Ihrer Kanäle an.

### Setup
1. Gehen Sie zu `Einstellungen > PeerTube Videos`
2. Fügen Sie Ihre Kanäle unter "Standard-Kanäle" ein:
```
ok_dessau
ok_magdeburg
okmq
ok_merseburg
```
3. Erstellen Sie eine Seite "Alle Kanäle"
4. Fügen Sie diesen Shortcode ein:

```
[pt-latest-per-channel]
```

**Ergebnis:** Vier Karten mit dem neuesten Video von jedem Kanal.

---

## 3. Dedizierte Kanal-Seite

Erstellen Sie eine eigene Seite für jeden Kanal mit allen Videos.

### Setup
1. Erstellen Sie eine Seite "OK Dessau"
2. Fügen Sie ein:

```html
<h1>OK Dessau - Offener Kanal Dessau</h1>
<p>Hier finden Sie alle Videos vom Offenen Kanal Dessau.</p>

[pt-channel-videos channel="ok_dessau" count="12"]
```

Wiederholen Sie dies für jeden Kanal mit dem entsprechenden channel-Handle.

---

## 4. Video-Detailseite (statisch)

Erstellen Sie eine Seite für ein bestimmtes Video.

### Setup
1. Erstellen Sie eine Seite "Featured Video"
2. Fügen Sie ein:

```
[pt-video id="xc86cB87iZXsgCofjHVcYJ"]
```

**Ergebnis:** Vollständige Video-Ansicht mit Player, Beschreibung und allen Metadaten.

---

## 5. Video-Detailseite (dynamisch mit Video-Nummer)

Ideal für Videos mit Video-Nummern aus dem peertube-plugin-okas-dev.

### Setup
1. Erstellen Sie eine Seite "Video"
2. Fügen Sie ein:

```
[pt-video number="12345"]
```

**Verwendung:** Verlinken Sie auf diese Seite mit verschiedenen Video-Nummern als Parameter.

---

## 6. Suchfunktion

Fügen Sie eine Suchfunktion für Ihre Video-Mediathek hinzu.

### Setup A: Alles auf einer Seite

Erstellen Sie eine Seite "Video-Suche" mit:

```
<h1>Video-Suche</h1>
[pt-search placeholder="Nach Videos suchen..."]

<h2>Suchergebnisse</h2>
[pt-search-results per_page="12"]
```

### Setup B: Getrennte Seiten

**Seite 1: "Suche" (/suche/)**
```
<h1>Video-Suche</h1>
[pt-search action="/suchergebnisse/"]
```

**Seite 2: "Suchergebnisse" (/suchergebnisse/)**
```
<h1>Suchergebnisse</h1>
[pt-search-results per_page="15"]
```

---

## 7. Homepage mit Featured Videos

Zeigen Sie auf der Homepage eine kleine Auswahl an Videos.

### Setup
Auf Ihrer Homepage:

```html
<section class="featured-videos">
    <h2>Aktuelle Videos</h2>
    [pt-last-videos count="4"]
    <p><a href="/videos/">Alle Videos ansehen »</a></p>
</section>
```

---

## 8. Sidebar-Widget mit neuesten Videos

Nutzen Sie das "HTML"-Widget in Ihrer Sidebar.

### Setup
1. Gehen Sie zu `Design > Widgets`
2. Fügen Sie ein "Benutzerdefiniertes HTML"-Widget hinzu
3. Inhalt:

```html
<h3>Neueste Videos</h3>
[pt-latest-per-channel channels="ok_dessau,ok_magdeburg"]
```

**Hinweis:** Passen Sie das CSS eventuell an für eine kompaktere Darstellung.

---

## 9. Archiv-Seite mit Jahresfilter

Kombinieren Sie mehrere Shortcodes für ein Archiv.

### Setup
Erstellen Sie eine Seite "Video-Archiv 2024":

```html
<h1>Video-Archiv 2024</h1>

<h2>Alle Kanäle</h2>
[pt-latest-per-channel]

<hr>

<h2>OK Dessau</h2>
[pt-channel-videos channel="ok_dessau" count="8"]

<h2>OK Magdeburg</h2>
[pt-channel-videos channel="ok_magdeburg" count="8"]

<h2>OKMQ</h2>
[pt-channel-videos channel="okmq" count="8"]
```

---

## 10. Landing Page für Spezial-Event

Erstellen Sie eine Seite für eine spezielle Video-Serie oder ein Event.

### Setup
Seite "Themenabend Klimawandel":

```html
<h1>Themenabend: Klimawandel</h1>
<p>Eine Sammlung unserer Videos zum Thema Klimawandel.</p>

[pt-search placeholder="Weitere Videos zum Thema suchen..."]
[pt-search-results per_page="8"]

<hr>

<h2>Empfohlenes Video</h2>
[pt-video id="ABC123XYZ"]
```

---

## 11. Multi-Kanal Übersicht

Zeigen Sie Videos von spezifischen Kanälen basierend auf Thema.

### Setup
Seite "Regional-Nachrichten":

```
<h1>Regional-Nachrichten</h1>
[pt-latest-per-channel channels="ok_dessau,ok_magdeburg,ok_merseburg"]
```

---

## 12. Responsive Navigation

Nutzen Sie WordPress-Menüs für Kanal-Navigation.

### Setup
1. Erstellen Sie eine Seite pro Kanal (siehe Beispiel 3)
2. Gehen Sie zu `Design > Menüs`
3. Fügen Sie alle Kanal-Seiten zum Menü hinzu
4. Struktur:
```
Videos
├── Alle Videos
├── Suche
├── Kanäle
│   ├── OK Dessau
│   ├── OK Magdeburg
│   ├── OKMQ
│   └── OK Merseburg
```

---

## 13. Blog-Post mit eingebettetem Video

Fügen Sie ein spezifisches Video in einen Blog-Post ein.

### Setup
In Ihrem Blog-Post:

```html
<h2>Unser neuestes Video</h2>
<p>Schauen Sie sich unser neuestes Interview an:</p>

[pt-video id="VIDEO_ID_HIER"]

<p>Was denken Sie über das Thema? Schreiben Sie es in die Kommentare!</p>
```

---

## 14. Themen-basierte Sammlung

Nutzen Sie die Suchfunktion für thematische Sammlungen.

### Setup
Seite "Dokumentationen":

```html
<h1>Dokumentationen</h1>
<p>Alle unsere Dokumentarfilme an einem Ort.</p>

<!-- Nutzer sucht nach "Dokumentation" -->
[pt-search placeholder="Dokumentationen durchsuchen..."]
[pt-search-results per_page="10"]
```

---

## 15. Mehrsprachige Seite

Nutzen Sie WPML oder Polylang für mehrsprachige Video-Seiten.

### Setup
**Deutsche Version (/de/videos/):**
```
<h1>Videos</h1>
[pt-last-videos count="12"]
```

**Englische Version (/en/videos/):**
```
<h1>Videos</h1>
[pt-last-videos count="12"]
```

Die Video-Titel und Beschreibungen kommen direkt von PeerTube und behalten ihre Originalsprache.

---

## Tipps für alle Beispiele

### Performance
- Verwenden Sie `count`-Attribute sinnvoll (nicht zu hoch)
- Nutzen Sie Caching-Plugins zusätzlich
- Lazy Loading ist standardmäßig aktiviert

### Design
- Passen Sie CSS in Ihrem Theme an
- Verwenden Sie Theme-Builder (Elementor, etc.) für Layout
- Testen Sie auf verschiedenen Bildschirmgrößen

### SEO
- Fügen Sie Meta-Beschreibungen hinzu
- Nutzen Sie beschreibende Seitentitel
- Erstellen Sie eine Sitemap mit allen Video-Seiten

### Wartung
- Leeren Sie den Cache nach größeren Änderungen
- Überprüfen Sie regelmäßig die Verbindung zur PeerTube-Instanz
- Aktualisieren Sie das Plugin bei neuen Versionen

---

## Kombinationen

Sie können Shortcodes auch kombinieren für komplexe Layouts:

```html
<div class="video-page">
    <section class="hero">
        <h1>Willkommen in unserer Mediathek</h1>
        [pt-search]
    </section>
    
    <section class="featured">
        <h2>Neuste Beiträge</h2>
        [pt-latest-per-channel]
    </section>
    
    <section class="all-videos">
        <h2>Alle Videos</h2>
        [pt-last-videos count="12"]
    </section>
</div>
```

Passen Sie dann das CSS an für das gewünschte Layout.

---

## Support

Wenn Sie Fragen zu diesen Beispielen haben oder Hilfe bei der Umsetzung benötigen:

1. Überprüfen Sie die vollständige Dokumentation
2. Schauen Sie in die FAQ
3. Erstellen Sie ein Issue auf GitHub

**Viel Erfolg mit Ihrer Video-Plattform!** 🎬

