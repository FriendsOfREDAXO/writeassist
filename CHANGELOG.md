# Changelog

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
