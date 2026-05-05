#!/bin/bash

# 🚀 Documentation System Auto-Setup Script
# Usage: ./setup-docs.sh PROJECT_NAME
# Example: ./setup-docs.sh my-awesome-api

set -e

PROJECT_NAME=${1:-.}
PROJECT_PATH="$PROJECT_NAME"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Setting up documentation system for: $PROJECT_NAME${NC}\n"

# Create directories
echo -e "${YELLOW}📁 Creating directories...${NC}"
mkdir -p "$PROJECT_PATH/.github/ISSUE_TEMPLATE"
mkdir -p "$PROJECT_PATH/.github/PULL_REQUEST_TEMPLATE"
mkdir -p "$PROJECT_PATH/docs/issues"
mkdir -p "$PROJECT_PATH/docs/implementation"
mkdir -p "$PROJECT_PATH/docs/pull-requests"

# Create issue template
cat > "$PROJECT_PATH/.github/ISSUE_TEMPLATE/task.md" << 'EOF'
---
name: Development Task
about: Task untuk project development dengan checklist jelas
title: "[TASK] "
labels: development, pending
assignees: ''
---

## 📋 Title
[Judul task yang jelas]

## 📝 Description
[Deskripsi detail apa yang harus dikerjakan]

## 🔴 Problem
[Apa masalahnya? Mengapa task ini penting?]

## ✅ Expected Result
[Apa hasil yang diharapkan?]

## ✔️ Acceptance Criteria
Pastikan semua selesai sebelum mark as DONE:
- [ ] Requirement 1
- [ ] Requirement 2
- [ ] Requirement 3
- [ ] Code tested
- [ ] No errors
- [ ] Documentation written

## 🏷️ Labels
- Type: feature / fix / refactor / docs
- Priority: high / medium / low
- Complexity: easy / medium / hard

## ⏱️ Effort Estimate
[e.g., 2-3 hours]

## 📌 Notes
[Additional context]

---

## 🔄 Workflow
1. Assign to yourself
2. Create feature branch
3. Add comments untuk progress
4. Create PR referencing issue
5. After merge → Issue auto-closes
EOF

# Create PR template
cat > "$PROJECT_PATH/.github/PULL_REQUEST_TEMPLATE/pull_request.md" << 'EOF'
## Summary
[Brief description of changes]

## Type of Change
- [ ] New feature
- [ ] Bug fix
- [ ] Refactoring
- [ ] Documentation

## Related Issue
Closes #[ISSUE_NUMBER]

## Changes Made
- [Change 1]
- [Change 2]

## Testing Done
- [ ] Manual tested
- [ ] Unit tests
- [ ] Integration tests

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-reviewed
- [ ] Comments added
- [ ] Documentation updated
- [ ] No errors/warnings

## 🔍 Reviewer Checklist
- [ ] Code review completed
- [ ] Functionality verified
- [ ] Approved for merge
EOF

# Create TASK_LIST.md template
cat > "$PROJECT_PATH/TASK_LIST.md" << 'EOF'
# Task List - $PROJECT_NAME

## 📋 Overview
Project: $PROJECT_NAME

**Tracking:** GitHub Issues → All tasks tracked in [Issues](https://github.com/YOUR_ORG/$PROJECT_NAME/issues)

---

## Phase 1: Setup & Infrastructure

| # | Task | Issue | Status |
|---|------|-------|--------|
| 1.1 | [Task 1] | [#1](https://github.com/YOUR_ORG/$PROJECT_NAME/issues/1) | ⏳ |
| 1.2 | [Task 2] | [#2](https://github.com/YOUR_ORG/$PROJECT_NAME/issues/2) | ⏳ |

---

## 📊 Summary

| Metric | Value |
|--------|-------|
| **Total Tasks** | [X] |
| **Completed** | 0 |
| **In Progress** | 0 |
| **Pending** | [X] |
| **Progress** | 0% |

---

**Status:** 🚀 In Development
EOF

# Create GitHub documentation
cat > "$PROJECT_PATH/GITHUB_ISSUES.md" << 'EOF'
# 🐙 GitHub Issues Workflow

Quick guide untuk menggunakan GitHub Issues untuk task tracking.

---

## 🚀 Quick Start

### 1. View All Tasks
```
Go to: https://github.com/YOUR_ORG/$PROJECT_NAME/issues
```

### 2. Pick a Task
- Click issue dari list
- Read description & acceptance criteria

### 3. Start Working
```bash
git checkout -b feature/task-description
```

### 4. Commit dengan Reference
```bash
git commit -m "feat(scope): message Closes #1"
```

### 5. Create PR
```
PR description: Closes #1
```

### 6. Merge
GitHub auto-closes issue ✅

---

## 🏷️ Labels

- `phase-1` / `phase-2` etc - By phase
- `feature` / `fix` / `docs` - By type  
- `high-priority` / `medium` / `low` - By priority
- `in-progress` / `blocked` / `done` - By status

---

See README.md for more details
EOF

# Create README template
cat > "$PROJECT_PATH/README.md" << 'EOF'
# $PROJECT_NAME

[Project description here]

---

## 📋 Documentation

- **[GITHUB_ISSUES.md](./GITHUB_ISSUES.md)** - How to work with GitHub Issues
- **[TASK_LIST.md](./TASK_LIST.md)** - All tasks (links to GitHub issues)
- **[GIT_WORKFLOW.md](./GIT_WORKFLOW.md)** - Git & commit standards

---

## 🚀 Quick Start

[Add project-specific setup instructions]

---

## 🤝 Contributing

1. Pick issue from [GitHub Issues](https://github.com/YOUR_ORG/$PROJECT_NAME/issues)
2. Create feature branch: `git checkout -b feature/name`
3. Commit: `git commit -m "feat(scope): message Closes #X"`
4. Create PR

---

## 📞 Support

[Add contact/support info]

---

Last Updated: $(date +%Y-%m-%d)
EOF

# Create GIT_WORKFLOW.md
cat > "$PROJECT_PATH/GIT_WORKFLOW.md" << 'EOF'
# 🔄 Git Workflow & Commit Standards

---

## 🌿 Branch Naming
```
feature/task-description
fix/bug-description
refactor/component-name
docs/section-name
```

---

## 💬 Conventional Commits

```
feat(scope): message
fix(scope): message
refactor(scope): message
docs(scope): message
```

**Reference issues:**
```
git commit -m "feat: add login

Closes #1
"
```

---

## 🔄 Workflow

1. Create branch from main/develop
2. Code & test
3. Commit with reference
4. Create PR
5. Code review
6. Merge

---

For detailed guidelines, see GITHUB_ISSUES.md
EOF

# Create CHANGELOG.md
cat > "$PROJECT_PATH/CHANGELOG.md" << 'EOF'
# Changelog

All notable changes documented here.

---

## [Unreleased]

### Planned
[Upcoming features]

---

## [0.1.0] - $(date +%Y-%m-%d)

### Initial Setup
- ✨ Project initialization
- 📚 Documentation system setup
- 🔧 GitHub workflow configured

---

**Format:** [Keep a Changelog](https://keepachangelog.com/)

Last Updated: $(date +%Y-%m-%d)
EOF

echo -e "\n${GREEN}✅ Documentation files created successfully!${NC}\n"

echo -e "${YELLOW}📝 Next Steps:${NC}"
echo "1. Edit README.md with project description"
echo "2. Replace YOUR_ORG with your GitHub organization"
echo "3. Create issues on GitHub using templates"
echo "4. Update TASK_LIST.md with actual tasks"
echo "5. Start developing!"

echo -e "\n${GREEN}🎉 Setup Complete!${NC}\n"
EOF
