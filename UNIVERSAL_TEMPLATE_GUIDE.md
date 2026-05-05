# 📦 Universal Documentation System Template

**For All Your GitHub Projects** - Backend, Frontend, Infra, Mobile, etc.

---

## 🎯 What Is This?

Complete, production-ready documentation system yang bisa di-apply ke ANY project:
- ✅ Generic (works for all tech stacks)
- ✅ Professional (follows best practices)
- ✅ Reusable (easy to setup for new projects)
- ✅ GitHub native (uses issues & PRs)
- ✅ Automated (bash script setup)

---

## 📂 What You Get

### 1. **Bash Setup Script** (RECOMMENDED)
```bash
./setup-docs.sh project-name
# Creates all docs in seconds
```

### 2. **Local Template Folder**
```
documentation-template/
├── .github/
│   ├── ISSUE_TEMPLATE/task.md
│   └── PULL_REQUEST_TEMPLATE/pull_request.md
├── README.md
├── TASK_LIST.md
├── GITHUB_ISSUES.md
├── GIT_WORKFLOW.md
└── CHANGELOG.md
```

### 3. **GitHub Template Repository**
```
repo: github-username/documentation-template (template repo)
↓
Use as template for new projects
↓
Auto-copy all docs
```

---

## 🚀 3 Ways to Use

### Method 1: Bash Script (FASTEST)

```bash
# Download setup script (one time)
curl -O https://raw.githubusercontent.com/YOUR_ORG/documentation-template/main/setup-docs.sh
chmod +x setup-docs.sh

# For each new project:
./setup-docs.sh my-project-name
cd my-project-name
git init
# Start working!
```

**Pros:**
- ✅ Fastest (30 seconds)
- ✅ Automated
- ✅ One command

**Cons:**
- Need to run script for each project

---

### Method 2: GitHub Template Repository (EASIEST)

```
Go to GitHub:
https://github.com/YOUR_ORG/documentation-template

Click: "Use this template"
Name your project
Create repo

Done! All docs ready!
```

**Pros:**
- ✅ One click
- ✅ Full GitHub integration
- ✅ Ready to clone & use

**Cons:**
- Need to maintain template repo on GitHub

---

### Method 3: Manual Copy (FLEXIBLE)

```bash
# Clone documentation-template
git clone https://github.com/YOUR_ORG/documentation-template.git
cd documentation-template

# Copy to your new project
cp -r . ../my-new-project/

cd ../my-new-project
# Remove git history (if needed)
rm -rf .git
git init
# Ready to go!
```

**Pros:**
- ✅ Flexible
- ✅ Can customize before copying
- ✅ No script needed

**Cons:**
- Manual process

---

## 📋 File Structure After Setup

```
your-project/
├── .github/
│   ├── ISSUE_TEMPLATE/
│   │   └── task.md                    # Issue template
│   └── PULL_REQUEST_TEMPLATE/
│       └── pull_request.md            # PR template
│
├── docs/
│   ├── issues/ (optional, for archive)
│   ├── implementation/ (optional)
│   └── pull-requests/ (optional)
│
├── README.md                          # Project overview
├── TASK_LIST.md                       # All tasks → GitHub issues
├── GITHUB_ISSUES.md                   # Workflow guide
├── GIT_WORKFLOW.md                    # Commit standards
├── CHANGELOG.md                       # Release notes
└── .gitignore, etc.
```

---

## ✅ Quick Setup (All Methods)

### After Creating Project

1. **Replace Placeholders**
   ```bash
   # In all .md files:
   YOUR_ORG → your-github-org
   $PROJECT_NAME → your-project-name
   ```

2. **Push to GitHub**
   ```bash
   git add .
   git commit -m "docs: add documentation system"
   git push origin main
   ```

3. **Create First Issues**
   ```
   Go to: https://github.com/your-org/your-project/issues
   Click: New Issue
   Choose: Development Task template
   Fill: Title, description, labels
   ```

4. **Update TASK_LIST.md**
   ```
   Add your actual tasks
   Link to GitHub issue numbers
   ```

5. **Start Development**
   ```bash
   git checkout -b feature/first-task
   # Start coding!
   ```

---

## 🎯 Included Files

### `.github/ISSUE_TEMPLATE/task.md`
Template untuk create issues dengan proper structure:
```
- Title
- Description
- Problem & Expected Result
- Acceptance Criteria
- Labels & Effort
```

### `.github/PULL_REQUEST_TEMPLATE/pull_request.md`
Auto-populated PR template dengan:
```
- Summary & Type
- Related Issue (Closes #X)
- Changes Made
- Testing Done
- Reviewer Checklist
```

### `README.md`
Project overview dengan:
- Quick description
- Documentation links
- Quick start guide
- Contributing guidelines

### `TASK_LIST.md`
All tasks organized dengan:
- Table format
- GitHub issue links
- Phase grouping
- Progress tracking

### `GITHUB_ISSUES.md`
Workflow guide dengan:
- Quick start
- Label reference
- Common patterns
- Examples

### `GIT_WORKFLOW.md`
Git standards dengan:
- Branch naming
- Conventional commits
- Commit examples
- Best practices

### `CHANGELOG.md`
Version history dengan:
- Keep a Changelog format
- Version entries
- Release notes template

---

## 💡 Key Features

### GitHub Integration
✅ Issue templates auto-suggest structure  
✅ PR templates remind of checklist  
✅ Auto-linking (Closes #1 in commits)  
✅ Auto-closing (merge PR → close issue)  

### Best Practices Baked In
✅ Conventional Commits format  
✅ Atomic commits recommended  
✅ Clear acceptance criteria  
✅ Proper labels & organization  

### Generic Enough For All Projects
✅ No tech-specific content  
✅ Works for Backend, Frontend, Infra, etc  
✅ Adaptable to any workflow  
✅ Simple but professional  

### Easy to Maintain
✅ Minimal files (just 6-7 .md files)  
✅ No external tools needed  
✅ Pure GitHub features  
✅ Easy to update  

---

## 🔄 Typical Workflow

```
1. Create new GitHub project
   ↓
2. Use setup-docs.sh or template repo
   ↓
3. Update README & TASK_LIST
   ↓
4. Create issues on GitHub
   ↓
5. Assign issues to developers
   ↓
6. Developer creates feature branch
   ↓
7. Developer commits: "feat: ... Closes #1"
   ↓
8. Developer creates PR
   ↓
9. Code review
   ↓
10. Merge PR → GitHub auto-closes issue #1 ✅
   ↓
11. Update CHANGELOG
   ↓
12. Next task!
```

---

## 🎨 Customization

### Minimal Changes Needed
```
1. YOUR_ORG → your-github-organization
2. $PROJECT_NAME → your-project-name
3. README.md → Add your specific info
4. TASK_LIST.md → Add your actual tasks
```

### Optional Enhancements
```
- Add CONTRIBUTING.md for detailed guidelines
- Add CODE_OF_CONDUCT.md for team rules
- Add ARCHITECTURE.md for technical decisions
- Add DEPLOYMENT.md for production info
```

---

## 🚀 Setting Up For All Your Projects

### Step 1: Choose Your Preferred Method

**Method 1 (Recommended): GitHub Template**
```
1. Create public repo: "documentation-template"
2. Add all files from template
3. Mark as "Template repository" in Settings
4. For each new project: Use template → Done!
```

**Method 2: Use Bash Script**
```
1. Save setup-docs.sh somewhere
2. For each project: Run ./setup-docs.sh project-name
3. Customize as needed
```

**Method 3: Local Template**
```
1. Keep template folder locally
2. Copy to each new project
3. Update placeholders
```

### Step 2: Use for New Projects

```bash
# Every new project follows same pattern
git clone https://github.com/your-org/documentation-template.git my-new-project
cd my-new-project

# Or if using script:
./setup-docs.sh my-new-project
cd my-new-project

# Update placeholders
sed -i 's/YOUR_ORG/your-actual-org/g' *.md

# Push to GitHub
git remote set-url origin https://github.com/your-org/my-new-project.git
git push -u origin main

# Create first issues
# Start developing!
```

---

## 📊 Benefits Summary

| Aspect | Benefit |
|--------|---------|
| **Setup Time** | 30 seconds - 2 minutes |
| **Learning Curve** | Low - clear templates |
| **Consistency** | All projects same structure |
| **Professional** | Production-ready docs |
| **Scalable** | Works for 1 or 100 projects |
| **Maintainability** | Minimal files to update |
| **GitHub Native** | Uses built-in features |
| **No Dependencies** | Pure Markdown & GitHub |

---

## ✨ Real World Examples

### Project 1: Laravel Backend
```
Same docs system
Custom README with Laravel setup
GitHub issues for features/bugs
```

### Project 2: React Frontend
```
Same docs system
Custom README with npm setup
GitHub issues for UI/UX tasks
```

### Project 3: DevOps/Infra
```
Same docs system
Custom README with deployment info
GitHub issues for infrastructure tasks
```

### Project 4: Mobile App
```
Same docs system
Custom README with mobile-specific setup
GitHub issues for mobile features
```

All using **exact same documentation template!**

---

## 🎯 Recommended: GitHub Template Repo

**Most Scalable Approach:**

1. **Create Public Template**
   ```
   Repo: https://github.com/your-org/documentation-template
   Settings → Template repository ✓
   ```

2. **For Each New Project**
   ```
   Click: Use this template
   Name: your-project-name
   Create repo
   Clone & start developing
   ```

3. **To Update All Projects**
   ```
   Update template repo
   Manually sync to existing projects
   Or: Use script to batch update
   ```

---

## 📞 Support & Troubleshooting

### "Script doesn't run"
```bash
chmod +x setup-docs.sh
./setup-docs.sh project-name
```

### "GitHub URLs not linking"
```
Replace YOUR_ORG with actual GitHub org
Example: your-org/your-project
```

### "Want to add more docs?"
```
Add: CONTRIBUTING.md, ARCHITECTURE.md, etc
Keep same professional format
Link from README
```

### "Need version-specific docs?"
```
Create branches for each version
Update CHANGELOG when merging
Keep main/master as latest
```

---

## 🎉 Ready to Go!

### Files You Have:
1. ✅ `.github/ISSUE_TEMPLATE/task.md`
2. ✅ `.github/PULL_REQUEST_TEMPLATE/pull_request.md`
3. ✅ `README.md` template
4. ✅ `TASK_LIST.md` template
5. ✅ `GITHUB_ISSUES.md` guide
6. ✅ `GIT_WORKFLOW.md` standards
7. ✅ `CHANGELOG.md` template
8. ✅ `setup-docs.sh` script

### For Your Next 10 Projects:

**Option 1 (Recommended):**
```bash
# Setup once
./setup-docs.sh project-1
./setup-docs.sh project-2
./setup-docs.sh project-3
# ... for all projects
```

**Option 2:**
```
Go to GitHub
Click: Use template
Create new repo
Done!
```

**Option 3:**
```
Copy template folder
Paste to new project
Update placeholders
Done!
```

---

**Happy Documenting! 🚀**

All your projects akan have professional-grade documentation system!

---

Last Updated: May 5, 2026
Created for: All GitHub projects (Backend, Frontend, Infra, Mobile, etc)
Reusability: 100% ✅
