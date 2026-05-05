# 🏗️ Setting Up GitHub Template Repository

Step-by-step guide untuk create reusable documentation template di GitHub.

---

## 📋 What Will Happen

You'll create a GitHub repository yang bisa di-"Use as template" untuk semua project baru:

```
https://github.com/YOUR_ORG/documentation-template
         ↓ (Use as template)
https://github.com/YOUR_ORG/my-new-project-1
https://github.com/YOUR_ORG/my-new-project-2
https://github.com/YOUR_ORG/my-new-project-3
```

---

## 🚀 Step 1: Create New Repository on GitHub

### Go to GitHub

1. **Login** ke https://github.com
2. **Go to** https://github.com/new
3. **Fill In:**

```
Repository name: documentation-template
Description: Universal documentation system for all projects
Visibility: Public (so it's reusable)
Initialize: Add a README
License: MIT (or your preference)
```

4. **Click:** Create repository

---

## 📥 Step 2: Add Files to Template Repo

### Option A: Via GitHub Web UI (Easy)

1. Go to: `https://github.com/YOUR_ORG/documentation-template`
2. **Add files** → **Create new file**
3. Repeat for each file:

**Create: `.github/ISSUE_TEMPLATE/task.md`**
- Click: Add file → Create new file
- Name: `.github/ISSUE_TEMPLATE/task.md`
- Paste: [Content from setup-docs.sh task template]
- Commit: `docs: add issue template`

**Create: `.github/PULL_REQUEST_TEMPLATE/pull_request.md`**
- Click: Add file → Create new file
- Name: `.github/PULL_REQUEST_TEMPLATE/pull_request.md`
- Paste: [PR template content]
- Commit: `docs: add PR template`

**Create: Other files** (README, TASK_LIST, etc)

### Option B: Via Git (Faster)

```bash
# Clone repository
git clone https://github.com/YOUR_ORG/documentation-template.git
cd documentation-template

# Create directory structure
mkdir -p .github/ISSUE_TEMPLATE
mkdir -p .github/PULL_REQUEST_TEMPLATE
mkdir -p docs/{issues,implementation,pull-requests}

# Create files (copy from backend project)
# .github/ISSUE_TEMPLATE/task.md
# .github/PULL_REQUEST_TEMPLATE/pull_request.md
# README.md
# TASK_LIST.md
# etc.

# Commit
git add .
git commit -m "docs: initial template setup"
git push origin main
```

---

## 🎯 Step 3: Mark as Template Repository

1. Go to: `https://github.com/YOUR_ORG/documentation-template`
2. **Settings** (tab at top)
3. Scroll down to: **"Template repository"**
4. **Check:** ✓ Template repository
5. **Save**

---

## ✅ Step 4: Files to Include

### Essential Files

```
documentation-template/
├── .github/
│   ├── ISSUE_TEMPLATE/
│   │   ├── task.md                    ← Issue template
│   │   ├── bug.md (optional)
│   │   └── feature.md (optional)
│   │
│   └── PULL_REQUEST_TEMPLATE/
│       └── pull_request.md            ← PR template
│
├── docs/                              ← Optional (for archive)
│   ├── .gitkeep
│
├── README.md                          ← Project template
├── TASK_LIST.md                       ← Tasks template
├── GITHUB_ISSUES.md                   ← Workflow guide
├── GIT_WORKFLOW.md                    ← Git standards
├── CHANGELOG.md                       ← Version history
├── .gitignore
└── LICENSE (MIT recommended)
```

### Content for Each File

#### `.github/ISSUE_TEMPLATE/task.md`
```markdown
---
name: Development Task
about: Task development template
title: "[TASK] "
labels: development, pending
---

## Title
[Clear title]

## Description
[What to do?]

## Acceptance Criteria
- [ ] Done?
```

#### `.github/PULL_REQUEST_TEMPLATE/pull_request.md`
```markdown
## Summary
[What changed?]

## Type
- [ ] Feature
- [ ] Fix
- [ ] Docs

## Related Issue
Closes #[ISSUE]

## Testing
- [ ] Tested

## Checklist
- [ ] Reviewed
```

#### `README.md`
```markdown
# Project Name Template

Documentation system for [PROJECT_TYPE].

## Quick Start

1. Read [GITHUB_ISSUES.md](./GITHUB_ISSUES.md)
2. Check [TASK_LIST.md](./TASK_LIST.md)
3. Follow [GIT_WORKFLOW.md](./GIT_WORKFLOW.md)

## Contributing

Use GitHub Issues for task tracking.
```

#### `TASK_LIST.md`
```markdown
# Task List

## Overview
Project: [PROJECT_NAME]

## Tasks

| # | Task | Issue | Status |
|---|------|-------|--------|
| 1 | [Task] | [#1](../) | ⏳ |

See [GITHUB_ISSUES.md](./GITHUB_ISSUES.md) for details.
```

#### `GITHUB_ISSUES.md`
```markdown
# GitHub Issues Workflow

## Quick Start

1. Go to Issues
2. Pick task
3. Create branch: git checkout -b feature/name
4. Commit: git commit -m "feat: ... Closes #1"
5. Create PR
6. Merge → auto-close

## Labels
- phase-1, phase-2, etc
- feature, fix, docs
- high, medium, low priority
```

#### `GIT_WORKFLOW.md`
```markdown
# Git Workflow

## Branch Names
- feature/task-name
- fix/bug-name
- docs/section

## Commits
- feat(scope): message
- fix(scope): message
- docs(scope): message

## Reference Issues
git commit -m "feat: ... Closes #1"
```

#### `CHANGELOG.md`
```markdown
# Changelog

## [Unreleased]
[Planned features]

## [0.1.0] - [DATE]
### Initial Setup
- Project initialization

Format: Keep a Changelog
```

---

## 🎯 Step 5: Verify Template

### Go to Your Template Repo

```
https://github.com/YOUR_ORG/documentation-template
```

### Check:
- ✅ All files visible
- ✅ `.github` folder exists
- ✅ Templates showing in folders
- ✅ Template repository checkbox is marked

### Test Template (Important!)

1. **Create test project** from template:
   - Go to template repo
   - Click: **"Use this template"** button
   - Name: `test-project-from-template`
   - Create

2. **Verify it worked:**
   - All files copied ✓
   - `.github` folder present ✓
   - README visible ✓
   - Issue template available ✓

3. **Delete test project**:
   - Settings → Delete this repository

---

## 📦 Step 6: Using Template for New Projects

### For Each New Project

1. **Go to template repo:**
   ```
   https://github.com/YOUR_ORG/documentation-template
   ```

2. **Click: "Use this template"** button (green button at top)

3. **Fill in:**
   ```
   Owner: YOUR_ORG
   Repository name: my-new-project
   Description: [Your project]
   Visibility: Public (or Private)
   Include all branches: (optional)
   ```

4. **Click: "Create repository from template"**

5. **Clone & Setup:**
   ```bash
   git clone https://github.com/YOUR_ORG/my-new-project.git
   cd my-new-project
   
   # Update placeholders
   sed -i 's/PROJECT_NAME/my-new-project/g' *.md
   
   # Update README with specific info
   # Update TASK_LIST with actual tasks
   
   git add .
   git commit -m "docs: customize for project"
   git push
   ```

6. **Create issues** from GitHub Issues

7. **Start developing!**

---

## 🔄 Updating Template (Future)

### When You Improve Documentation

1. **Update template repo:**
   ```bash
   git clone https://github.com/YOUR_ORG/documentation-template.git
   cd documentation-template
   
   # Make changes
   vim README.md
   vim GITHUB_ISSUES.md
   
   git add .
   git commit -m "docs: improve templates"
   git push
   ```

2. **For existing projects** - Manually sync or auto-script:
   ```bash
   # Option 1: Manual update per project
   git pull origin main  # Get latest template
   
   # Option 2: Write script to batch update
   # (advanced, optional)
   ```

---

## 💡 Tips & Tricks

### Quick Access
```
Pin template repo: https://github.com/YOUR_ORG/documentation-template
```

### Customize Per Project Type
```
Keep main template generic
But you can modify after creating from template:
- Backend projects: Add Docker setup info
- Frontend projects: Add npm setup info
- Infra projects: Add deployment info
```

### Share With Team
```
Every team member can use:
https://github.com/YOUR_ORG/documentation-template

They can create new projects from it
```

### Version Control Template
```
Keep improvement history in template repo
Tag versions: v1.0, v1.1, etc
```

---

## ✅ Complete Checklist

- [ ] Created `documentation-template` repo on GitHub
- [ ] Added `.github/ISSUE_TEMPLATE/task.md`
- [ ] Added `.github/PULL_REQUEST_TEMPLATE/pull_request.md`
- [ ] Added `README.md` template
- [ ] Added `TASK_LIST.md` template
- [ ] Added `GITHUB_ISSUES.md` guide
- [ ] Added `GIT_WORKFLOW.md` guide
- [ ] Added `CHANGELOG.md` template
- [ ] Added `.gitignore` (with Node, Python, etc)
- [ ] Marked as template repository ✓
- [ ] Tested creating project from template
- [ ] Deleted test project
- [ ] Ready to use!

---

## 🎉 Ready!

Your GitHub Template Repository is ready to use!

### For Your Next 10 Projects:

```
1. Go to: https://github.com/YOUR_ORG/documentation-template
2. Click: Use this template
3. Name your project
4. Create
5. Clone & customize
6. Start developing!
```

### All projects get:
- ✅ Professional documentation structure
- ✅ GitHub Issues workflow
- ✅ PR templates
- ✅ Git standards
- ✅ Task tracking
- ✅ Changelog management

---

**Consistency & Professional Standards** across all your GitHub projects! 🚀

---

Next: Start using template for your new projects!
