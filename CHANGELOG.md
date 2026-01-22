# Changelog

## [1.1.0] - 2026-01-22
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
