# Changelog

## [2.4.0] - 2026-04-21
### Fixed
- **Bulk-Translate**: Checkbox „Nur noch nicht übersetzte Namen" saß außerhalb des Fieldsets – Bootstrap-3-Struktur korrigiert (`div.checkbox > label > input` statt `label.checkbox > input`)

### Changed
- **InfoCenter-Widget**: Tabs werden nur noch angezeigt, wenn der zugehörige Service konfiguriert ist (Übersetzer-Tab nur bei vorhandenem DeepL-Key, Generator-Tab nur bei konfigurierter KI). LanguageTool (Improve) ist immer verfügbar (kostenlose Public-API).
- **AI-Provider**: Neue Option „Deaktiviert" im Provider-Select deaktiviert den Generator-Tab explizit.
- **AI-Provider**: Default-URL für OpenWebUI-Provider war `http://localhost:3000` und sorgte dafür, dass der Generator-Tab stets als konfiguriert galt – jetzt leer.
- **AI-Provider**: Provider-Einstellungsblöcke und Verbindungstest-Button werden server- und clientseitig korrekt aus-/eingeblendet (bei „Deaktiviert" alles versteckt).
- **Settings**: Notices mit HTML-Links (`api_key_notice`, `gemini_api_key_notice`) nutzen jetzt `rex_i18n::rawMsg()` statt `i18n()`, damit Links korrekt gerendert werden.
- **Verbindungstest**: Hinweis ergänzt, dass vor dem Test gespeichert werden muss.
- **Sidebar**: KI-Provider zeigt bei „Deaktiviert" nun korrekt „Deaktiviert" statt fälschlicherweise den vorherigen Provider als konfiguriert.

### Added
- **OpenAI-Provider**: Neuer Provider „OpenAI (ChatGPT)" mit dediziertem Formularblock (API-Key + Modell-Auswahl). Nutzt die offizielle OpenAI-API (`api.openai.com/v1`) – keine Base-URL-Eingabe nötig. Verfügbare Modelle: `gpt-4o-mini`, `gpt-4o`, `o4-mini`, `o3`.
- **Gemini-Modelle aktualisiert**: Modell-Auswahl enthält jetzt aktuelle Modelle (Gemini 2.5 Flash, 2.5 Flash Lite, 2.5 Pro, 3 Flash Preview, 3.1 Pro Preview). `gemini-3-pro-preview` wurde entfernt (eingestellt am 09.03.2026).

## [2.3.1] - 2026-03-11
### Fixed
- Syntaxfehler (fehlendes Semikolon) in `pages/settings.php` – führte zu Weißseite im Backend

## [2.3.0] - 2026-03-11
### Added
- **Auto-Übersetzen bei Umbenennung**: Neue Option `Übersetzung bei Umbenennung`, die Artikel- und Kategorienamen automatisch per DeepL in alle anderen Sprachen übersetzt, wann immer der Name gespeichert wird.
- Neue Methode `AutoTranslateService::isRenameEnabled()` für die neue Funktion.
- `ART_UPDATED`- und `CAT_UPDATED`-Extension-Point-Handler in `boot.php`.
- Einstellung `translate_on_rename` in der Backend-Einstellungsseite (Bereich Integrationen).
- Sidebar in den Einstellungen zeigt nun beide Auto-Übersetze-Optionen separat an.

## [2.2.1] - 2026-03-10
### Fixed
- **Bulk-Translate**: JS wird jetzt korrekt über `boot.php` geladen (statt in der Page-Datei, wo es zu spät war)
- **Bulk-Translate**: API auf `published = false` gesetzt (nur Backend)
- **Bulk-Translate**: `rex-api-call` korrekt als GET-Parameter in der URL

## [2.2.0] - 2026-03-10
### Added
- **Massenübersetzung**: Neue Backend-Seite zum Übersetzen bestehender Artikel- und Kategorienamen aus einer Quellsprache in alle anderen activen Sprachen via DeepL.
- **Option „nur unübersetzte"**: Überspringt Einträge, die in der Zielsprache bereits einen abweichenden Namen haben.
- Neue `AutoTranslateService`-Methoden `getTargetCode()` und `getSourceCode()` als öffentliche Aliases für die DeepL-Codemapping-Logik.

## [2.1.0] - 2026-03-10
### Added
- **Auto-Übersetzen**: Neue Artikel und Kategorien werden beim Anlegen automatisch per DeepL in alle aktiven Sprachen übersetzt.
- **Einstellung**: Option `enable_auto_translate` in den Einstellungen, erfordert hinterlegten DeepL-API-Key.
- **Robustheit**: Übersetzung läuft nach Abschluss aller REDAXO-internen Datenbank-Inserts (via `register_shutdown_function`), verhindert Überschreiben durch REDAXO-Core.

### Changed
- **Settings-Seite**: Zweispaltiges Layout mit Status-Sidebar (Auto-Übersetzen, API-Status, Integrationen).
- **JavaScript**: Settings-JS in separate Datei `assets/js/writeassist-settings.js` ausgelagert, `rex:ready`-Event statt `DOMContentLoaded`.

## [2.0.0] - 2026-01-22
### Added
- **AI Provider Architecture**: Support for Google Gemini and OpenAI Compatible (OpenWebUI/Ollama) providers.
- **TinyMCE Integration**: New `writeassist_generate` plugin for AI text generation, rewriting, summarizing, and expanding directly within the editor.
- **Prompt Management**: Save, load, and delete custom prompt templates in the Generator.
- **Improved Generator UI**: Added "Instructions" field alongside the Topic field for more precise control.
- **InfoCenter Widget**: Added a new "Generator" tab to the dashboard widget for quick access to AI tools.
- **Security Awareness**: Dynamic labels in settings and code generator to indicate which service is processing data.

### Changed
- **Refactoring**: Moved specific provider logic into individual Provider classes (`WriteAssistAiProviderGemini`, `WriteAssistAiProviderOpenAiCompatible`) behind a Factory.
- **UI Enhancements**: Improved labels and descriptions in backend settings and tools.
- **Fixes**: Resolved method duplication issues in API wrapper.
