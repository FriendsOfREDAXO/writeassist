# WriteAssist für REDAXO

Schreibhilfe mit KI-Power direkt im REDAXO Backend. Übersetzen, Texte verbessern, generieren – alles an einem Ort.

## Was kann das Ding?

### Übersetzung (DeepL)
Texte fix übersetzen, direkt im Backend oder im TinyMCE Editor. HTML-Formatierung bleibt erhalten, Quellsprache wird automatisch erkannt. Es gibt auch ein Widget für das Info Center. Andere in Vorbereitung. 

### Textverbesserung (LanguageTool)
Grammatik, Rechtschreibung, Stil – alles wird geprüft. Läuft über die öffentliche API oder einen eigenen Server per Docker.

### KI-Textgenerator (Google Gemini)
Texte umschreiben, kürzen, erweitern oder komplett neu aus einem Thema generieren. Eigene Prompts gehen auch.

### Code-Assistent (nur Admins)
REDAXO-Code generieren, erklären lassen oder verbessern. Kennt die Core-Klassen, installierte AddOns und die Datenbankstruktur.

**Aber:** Das ist ein Experiment – kein Ersatz für richtige Tools wie GitHub Copilot. Taugt für schnelle REDAXO-Fragen und einfache Snippets, mehr nicht.

## Installation

1. AddOn nach `redaxo/src/addons/` entpacken
2. Im Backend installieren und aktivieren
3. API-Schlüssel in den Einstellungen eintragen

## API-Schlüssel holen

| Service | Wo gibts den Key? | Kostenlos? |
|---------|-------------------|------------|
| DeepL | https://www.deepl.com/pro-api | 500k Zeichen/Monat |
| Google Gemini | https://aistudio.google.com/apikey | Großzügiges Kontingent |
| LanguageTool | Öffentliche API oder eigener Server | Ja |

## TinyMCE einrichten

Im TinyMCE-Profil das Plugin `writeassist_translate` aktivieren und den Button zur Toolbar packen:

```
undo redo | styles | bold italic | writeassist_translate | link
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
