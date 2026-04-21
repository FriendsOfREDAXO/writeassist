# WriteAssist für REDAXO

**Dein KI-Texter, tief integriert in den Workflow.**

WriteAssist bringt die volle Power moderner KI-Tools direkt in dein REDAXO Backend. Egal ob du Texte übersetzen, Grammatik korrigieren oder komplett neuen Content generieren willst – WriteAssist ist überall da, wo du es brauchst.

Voll integriert als Toolbar-Button im TinyMCE, als Info-Center-Widget oder als dedizierter Coding-Assistent. Dein Schweizer Taschenmesser für Content & Code.

## Features im Überblick

### 🌐 Übersetzung (DeepL)
Übersetze Content mit einem Klick, ohne das Layout zu zerschießen.
- **Nahtlos**: Funktioniert direkt im TinyMCE Editor und behält HTML-Formatierung bei.
- **Smart**: Automatische Spracherkennung.
- **Griffbereit**: Auch als Info-Center-Widget verfügbar.

### ✨ Textverbesserung (LanguageTool)
Schluss mit Tippfehlern und holprigen Sätzen.
- Prüft Grammatik, Rechtschreibung und Stil.
- Flexibel: Nutze die öffentliche API oder hoste deinen eigenen dockerisierten LanguageTool-Server für maximale Privacy.

### 🪄 KI-Textgenerator
Lass dir beim Schreiben helfen – vom Umschreiben bis zum Brainstorming.
- **Texte optimieren**: Kürzen, verlängern, Tonalität ändern.
- **Content erschaffen**: Blogposts, Teaser oder Beschreibungen aus Stichpunkten generieren.

**Unterstützte KI-Modelle:**
*   **Google Gemini**: Schnell, smart und mit großzügigem kostenlosen Kontingent.
*   **OpenWebUI / OpenAI Compatible**: Volle Freiheit! Nutze lokal gehostete Modelle (Ollama, vLLM) oder jede API, die OpenAI-kompatibel ist (z.B. Mistral, Groq).
*   **Prompt Management**: Speichere deine besten Befehle als Vorlagen für das ganze Team.

### 💻 Code-Assistent (Admin-Only)
Dein Sparringspartner für REDAXO-Entwicklung.
- Generiert Modul-Code, erklärt komplexe Klassen oder hilft beim Debuggen.
- **Weiß Bescheid**: Kennt Core-Klassen und installierte AddOns via Datenbank-Scan.
- *Hinweis: Ideal für schnelle Snippets und Fragen, ersetzt aber keinen erfahrenen Entwickler.*

## Integrationen

### Info Center Widget
WriteAssist klinkt sich direkt in das [Info Center AddOn](https://github.com/KLXM/redaxo-info-center) ein. Damit hast du deine Text-Tools direkt auf dem Dashboard.
- **Tab-Interface**: Schnelles Umschalten zwischen Übersetzen, Optimieren und Generieren.
- **Unabhängig**: Arbeite an Texten, ohne einen Artikel öffnen zu müssen.

### TinyMCE Editor
Die Tools sind da, wo du schreibst.
- **Buttons in der Toolbar**: Funktionen auf Knopfdruck abrufbar.
- **Kontext-Aware**: Liest den markierten Text oder den ganzen Editor-Inhalt.

### 🔄 Auto-Übersetzen bei Anlage
Wenn eine mehrsprachige REDAXO-Installation mit DeepL betrieben wird, kann WriteAssist neue Artikel und Kategorien automatisch in alle aktiven Sprachen übersetzen – ohne manuellen Eingriff.

- **Automatisch**: Wird ausgelöst, sobald ein Artikel oder eine Kategorie angelegt wird.
- **Alle Sprachen sofort**: Alle aktiven Clangs werden beim Speichern direkt befüllt.
- **Quellerkennung**: Die Ausgangssprache wird anhand der aktuellen Clang bestimmt.
- **Steuerbar**: Lässt sich in den WriteAssist-Einstellungen ein- und ausschalten.

**Voraussetzungen:**
- DeepL API-Key in den WriteAssist-Einstellungen hinterlegt
- Option *Auto-Übersetzen* aktiviert

### 🌍 Massenübersetzung bestehender Namen
Für bereits vorhandene Artikel und Kategorien gibt es eine eigene Backend-Seite: Quellsprache auswählen, Typ wählen und mit einem Klick alle Namen übersetzen.

- Übersetzt Artikel- und/oder Kategorienamen in einem Durchgang
- Option „nur unübersetzte Namen" schützt bereits angepasste Titel
- Nur für Admins sichtbar

--------------------------------------------------------------------------------

## Installation

1. AddOn nach `redaxo/src/addons/` entpacken
2. Im Backend installieren und aktivieren
3. API-Schlüssel in den Einstellungen eintragen

## API-Schlüssel holen

| Service | Wo gibts den Key? | Kostenlos? |
|---------|-------------------|------------|
| DeepL | https://www.deepl.com/ | 500k Zeichen/Monat |
| Google Gemini | https://aistudio.google.com/apikey | Großzügiges Kontingent |
| LanguageTool | Öffentliche API oder eigener Server | Ja |
| OpenWebUI / Ollama | Lokal installieren | Ja |

## TinyMCE einrichten

### 1. Übersetzer
Im TinyMCE-Profil das Plugin `writeassist_translate` aktivieren und den Button zur Toolbar packen:

```
undo redo | styles | bold italic | writeassist_translate | link
```

### 2. KI-Generator & Umschreiber
Im TinyMCE-Profil das Plugin `writeassist_generate` aktivieren.
Der Button `writeassist_generate` öffnet den Generator-Dialog.

Zusätzlich gibt es ein Kontextmenü: Text markieren -> Rechtsklick -> **KI: Umschreiben**.

```
undo redo | styles | bold italic | writeassist_translate writeassist_generate | link
```

## Eigener LanguageTool Server

Wer keine Limits will, startet einen eigenen Server:

```bash
docker run -d -p 8081:8010 erikvl87/languagetool
```

Dann in den Einstellungen `http://localhost:8081/v2/check` eintragen.

## Lizenz

MIT License

## Credits

**Friends Of REDAXO**  
Project Lead: [Thomas Skerbis](https://github.com/skerbis)
