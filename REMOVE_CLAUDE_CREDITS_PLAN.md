# Plan: Remove All Claude/Sonnet Developer Credits from Repository

## Current Status

### Git Commit History
Found **10+ commits** with "Co-Authored-By: Claude Sonnet" credits:
- `cedd44ac` - Add Africa Regional Pages view
- `fc3093e1` - Fix: Update TDIH button
- `462902399` - Fix lint issues in saho_ai_tdih
- `bfb863cc` - Bugfix/search and featured improvements
- `09f6222b` - Enhance featured articles page
- `e1190eb5` - Improve search results page styling
- `f1e5264a` - SAHO-PERF: Remove saho_webp module
- `58e90d0b` - SAHO-PERF: Fix responsive image rendering
- `a821ab5e` - SAHO-PERF: Fix responsive image implementation
- `3567e717` - SAHO-PERF: Fix Twig function registration

### Documentation/Code Files
Found **4 files** with Claude/Sonnet mentions:

**Keep These** (Legitimate references to AI services):
- `saho_tools.module` - ClaudeBot crawler directives (robots.txt) ✅ KEEP
- `llm-txt.html.twig` - ClaudeBot crawler documentation ✅ KEEP
- `SCHEMA_ORG.md` - "AI Citations in Claude" (usage metric) ✅ KEEP

**Remove These** (Developer credits):
- `README_SCHEMA.md` line 99 - "ClaudeBot" reference ✅ KEEP
- `README_SCHEMA.md` line 117 - "Increased mentions in ChatGPT, Claude" ✅ KEEP
- `README_SCHEMA.md` line 233 - **"Developer: SAHO Development Team + Claude Sonnet 4.5"** ❌ REMOVE

---

## Action Plan

### Phase 1: Documentation Cleanup (5 minutes)

**File to Edit**: `webroot/modules/custom/saho_tools/README_SCHEMA.md`

**Current (Line 233)**:
```markdown
**Developer**: SAHO Development Team + Claude Sonnet 4.5
```

**Change To**:
```markdown
**Developer**: SAHO Development Team
```

**Command**:
```bash
sed -i 's/SAHO Development Team + Claude Sonnet 4.5/SAHO Development Team/' \
  webroot/modules/custom/saho_tools/README_SCHEMA.md
```

---

### Phase 2: Git History Rewrite (15-30 minutes)

**WARNING**: This rewrites git history and requires force-push to all branches.

#### Option A: Rewrite Entire Main Branch History (Recommended)

**Risk**: Moderate - affects all contributors, requires coordination
**Benefit**: Complete removal from entire repository history

```bash
# 1. Backup current state
git branch backup-before-rewrite

# 2. Rewrite all commits on main branch
git filter-branch -f --msg-filter 'sed "/Co-Authored-By.*Claude/d"' \
  --tag-name-filter cat -- --all

# 3. Force push to remote (requires admin/force-push permissions)
git push origin main --force

# 4. Notify team to rebase their branches
# Each developer needs to run:
git fetch origin
git rebase origin/main
```

#### Option B: Rewrite Only Recent Commits (Conservative)

**Risk**: Low - only affects recent work
**Benefit**: Removes most visible credits without full history rewrite

```bash
# Count commits since a known "clean" point
git log --oneline | grep "v1.0.0"  # Find last release tag

# Rewrite last N commits (adjust N based on findings)
git filter-branch -f --msg-filter 'sed "/Co-Authored-By.*Claude/d"' HEAD~50..HEAD

# Force push
git push origin main --force
```

#### Option C: Create Clean Branch (No History Rewrite)

**Risk**: None - preserves full history
**Benefit**: No coordination needed, history intact
**Downside**: Old credits remain in git log

```bash
# 1. Create new branch from current main
git checkout -b main-clean

# 2. Credits remain in history but branch moves forward
# 3. Future commits won't have credits
git push origin main-clean

# 4. Update default branch in GitHub settings if desired
```

---

### Phase 3: Prevent Future Credits (5 minutes)

Create a git commit-msg hook to block Claude credits:

**File**: `.git/hooks/commit-msg`
```bash
#!/bin/bash
# Reject commits with Claude/Sonnet credits

if grep -q "Co-Authored-By.*Claude" "$1"; then
    echo "ERROR: Claude/Sonnet credits not allowed in commits"
    echo "Please remove 'Co-Authored-By: Claude' from commit message"
    exit 1
fi
```

**Setup**:
```bash
cat > .git/hooks/commit-msg << 'EOF'
#!/bin/bash
if grep -q "Co-Authored-By.*Claude" "$1"; then
    echo "ERROR: Claude/Sonnet credits not allowed in commits"
    exit 1
fi
EOF

chmod +x .git/hooks/commit-msg
```

**Distribute to Team**:
Add to `.githooks/commit-msg` and document in README:
```bash
# Team members run once after clone:
git config core.hooksPath .githooks
```

---

## Rollback Plan

If something goes wrong during history rewrite:

```bash
# 1. Restore from backup branch
git checkout main
git reset --hard backup-before-rewrite

# 2. Force push restore
git push origin main --force

# 3. Delete backup when confirmed safe
git branch -D backup-before-rewrite
```

---

## Communication Plan

### Before Rewrite (if using Option A or B)

**Slack/Email Announcement**:
```
🚨 Git History Rewrite Scheduled

When: [Date/Time]
Duration: 15 minutes
Impact: All developers must rebase active branches

Action Required After Rewrite:
1. git fetch origin
2. git rebase origin/main
3. git push --force (if you have pushed branches)

Reason: Removing AI assistant attribution from commit history

Questions? Contact @madsnorgaard
```

### During Rewrite

1. Lock main branch (GitHub settings → Branch protection → Temporarily disable)
2. Run rewrite commands
3. Verify with `git log --all --grep="Claude"`
4. Force push
5. Re-enable branch protection

### After Rewrite

**Verify Success**:
```bash
# Should return empty
git log --all --grep="Claude" --grep="Sonnet"

# Check GitHub PR/commit pages - @claude tags should be gone
gh pr list --state all --json number,title | head -10
```

---

## Timeline Estimate

| Phase | Duration | Risk Level |
|-------|----------|------------|
| Documentation cleanup | 5 min | None |
| Git history rewrite (Option A) | 15-30 min | Medium |
| Git history rewrite (Option B) | 10-15 min | Low |
| Clean branch (Option C) | 5 min | None |
| Prevention hook setup | 5 min | None |
| **Total (Option A)** | **25-40 min** | Medium |
| **Total (Option B)** | **20-25 min** | Low |
| **Total (Option C)** | **15 min** | None |

---

## Recommendation

**Use Option B (Recent Commits Only)** because:
1. ✅ Removes visible credits from recent/active PRs
2. ✅ Lower risk than full history rewrite
3. ✅ Minimal team coordination needed
4. ✅ Achieves goal without breaking workflows
5. ✅ Old commits rarely viewed anyway

**Skip rewriting commits older than 6 months** - they're archived and rarely accessed.

---

## Execution Checklist

- [ ] Phase 1: Remove "Claude Sonnet 4.5" from README_SCHEMA.md line 233
- [ ] Phase 1: Commit and push documentation fix
- [ ] Phase 2: Choose rewrite option (A, B, or C)
- [ ] Phase 2: Create backup branch
- [ ] Phase 2: Run git filter-branch
- [ ] Phase 2: Verify with git log
- [ ] Phase 2: Force push to origin/main
- [ ] Phase 2: Notify team to rebase (if Option A/B)
- [ ] Phase 3: Create commit-msg hook
- [ ] Phase 3: Test hook with dummy commit
- [ ] Phase 3: Document hook in README
- [ ] Verify: Check GitHub PR pages for @claude tags
- [ ] Verify: Search codebase for remaining mentions
- [ ] Cleanup: Delete backup branch (after 1 week)

---

## Post-Cleanup Verification

```bash
# 1. Check commit messages
git log --all --oneline | grep -i claude
git log --all --oneline | grep -i sonnet

# 2. Check file contents
grep -r "Claude Sonnet" webroot/modules/custom/ --exclude-dir=contrib
grep -r "Co-Authored-By.*Claude" .

# 3. Check GitHub UI
# - Browse recent PRs - @claude tags should be gone
# - Check commit pages - no "Co-authored-by" badges

# 4. Success criteria: All searches return empty
```

---

## Questions?

- **Q: Will this break anything?**
  A: No. Code functionality unchanged. Only commit metadata affected.

- **Q: What about branches already pushed?**
  A: They'll need rebasing after main is rewritten (Option A/B only).

- **Q: Can we undo this?**
  A: Yes, via backup branch created in Phase 2.

- **Q: What about GitHub PR history?**
  A: @claude tags disappear after force-push (may take minutes to refresh).

- **Q: Why keep ClaudeBot references?**
  A: ClaudeBot is Anthropic's web crawler - legitimate SEO/robots.txt directive.

---

**Status**: Ready to execute
**Last Updated**: 2026-02-08
**Document Version**: 1.0
