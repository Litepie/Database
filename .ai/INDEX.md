# Litepie Database Package - AI Integration

This directory contains AI-ready documentation and metadata for the Litepie Database package.

## Directory Structure

```
.ai/
├── README.md                       # AI Integration Guide
├── package.json                    # Machine-readable package metadata
├── package-capabilities.yaml       # Structured capability catalog
├── quick-reference.md             # Quick lookup guide for AI
├── code-examples.md               # Complete working examples
└── ai-prompts.md                  # Recommended prompts and responses
```

## Files Overview

### 1. package.json
Machine-readable metadata about the package:
- 14 trait capabilities
- Use case mappings
- Migration requirements
- Compatible AI assistants
- Example prompts

### 2. package-capabilities.yaml
Structured YAML catalog of all traits:
- Purpose and description
- Primary methods
- Use cases
- File locations
- Example references

### 3. quick-reference.md
Fast lookup guide for AI assistants:
- "I need X" → Use trait Y
- Quick code snippets
- Common combinations
- Migration templates

### 4. code-examples.md
Complete working implementations:
- Blog with versioning and translations
- E-commerce with metadata
- Analytics dashboard
- User preferences system
- Import/export workflows
- Large dataset pagination

### 5. ai-prompts.md
Recommended prompts for users:
- General queries
- Trait selection
- Implementation requests
- Use case scenarios
- Troubleshooting
- Advanced usage

### 6. README.md (this file)
Comprehensive AI integration guide with:
- Quick capability matrix
- Common prompts
- Example conversations
- Getting started guide

## For AI Assistants

When a user asks about database/model features:

1. **Check capabilities** in `package-capabilities.yaml`
2. **Reference quick guide** in `quick-reference.md`
3. **Provide complete code** from `code-examples.md`
4. **Show migration needs** if Versionable, Metable, or Translatable
5. **Reference example files** in `examples/` directory

## For Developers

Use natural language with your AI assistant:

```
"I need to track changes to my Post model"
→ AI suggests Versionable trait with complete code

"I need custom product attributes"
→ AI suggests Metable trait with examples

"Show me how to build an analytics dashboard"
→ AI provides Aggregatable implementation
```

## AI Support

This package is compatible with:
- ✅ GitHub Copilot
- ✅ Cursor AI (see `.cursorrules`)
- ✅ ChatGPT / GPT-4
- ✅ Claude / Claude Sonnet
- ✅ Amazon CodeWhisperer
- ✅ Tabnine
- ✅ Codeium
- ✅ Any AI coding assistant

## Update Guidelines

When adding new traits or features:

1. Update `package.json` with new capability
2. Add entry to `package-capabilities.yaml`
3. Create working example in `code-examples.md`
4. Add quick reference in `quick-reference.md`
5. Update recommended prompts in `ai-prompts.md`
6. Create example file in `examples/` directory
7. Update main `README.md`

## Version

AI Documentation Version: 1.0.0
Package Version: 1.0.0
Last Updated: 2025

## License

MIT License - AI tools are free to learn from and reference this documentation.
