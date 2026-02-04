# Automatic Deployment Setup for cPanel Hosting

This guide sets up automatic deployments from GitHub to your cPanel hosting when code is merged to `main`.

## Overview

When code is merged to `main`:
1. GitHub Actions triggers
2. Connects to cPanel server via SSH
3. Pulls latest code
4. Runs deployment scripts
5. Production goes live automatically

## Prerequisites

- cPanel hosting with SSH access enabled
- GitHub repository with Actions enabled
- Production and staging sites set up

## Step 1: Generate SSH Key for GitHub Actions

On your **local machine**, generate a dedicated SSH key for deployments:

```bash
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/saho_deploy_key -N ""
```

This creates:
- `~/.ssh/saho_deploy_key` (private key - for GitHub)
- `~/.ssh/saho_deploy_key.pub` (public key - for cPanel)

## Step 2: Add Public Key to cPanel Server

### Option A: Via cPanel SSH Access

1. Log into cPanel
2. Go to **Security → SSH Access**
3. Click **Manage SSH Keys**
4. Click **Import Key**
5. Paste contents of `~/.ssh/saho_deploy_key.pub`
6. Click **Import**
7. Find the imported key and click **Manage** → **Authorize**

### Option B: Via SSH (if you have existing access)

```bash
# Copy public key to server
cat ~/.ssh/saho_deploy_key.pub | ssh user@your-server.com "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"

# Set correct permissions
ssh user@your-server.com "chmod 700 ~/.ssh && chmod 600 ~/.ssh/authorized_keys"
```

## Step 3: Test SSH Connection

```bash
# Test the key works
ssh -i ~/.ssh/saho_deploy_key user@your-server.com "echo 'SSH connection successful'"
```

Should print: `SSH connection successful`

## Step 4: Configure GitHub Secrets

1. Go to your GitHub repository
2. Navigate to **Settings → Secrets and variables → Actions**
3. Click **New repository secret** for each:

| Secret Name | Value | Example |
|-------------|-------|---------|
| `DEPLOY_SSH_KEY` | Contents of `~/.ssh/saho_deploy_key` (private key) | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `DEPLOY_HOST` | Your cPanel server hostname | `server123.yourhostingprovider.com` |
| `DEPLOY_USER` | Your cPanel username | `sahistrg878` |
| `DEPLOY_PATH_PROD` | Full path to production site | `/home/sahistrg878/public_html/sahistory.org.za` |
| `DEPLOY_PATH_STAGING` | Full path to staging site | `/home/sahistrg878/public_html/staging.sahistory.org.za` |

### Getting the Private Key Contents

```bash
# Copy private key to clipboard (macOS)
cat ~/.ssh/saho_deploy_key | pbcopy

# Or print to terminal
cat ~/.ssh/saho_deploy_key
```

Copy the **entire** output including:
```
-----BEGIN OPENSSH PRIVATE KEY-----
...all the content...
-----END OPENSSH PRIVATE KEY-----
```

## Step 5: Set Up GitHub Environments (Optional but Recommended)

For better control and approval workflows:

1. Go to **Settings → Environments**
2. Create two environments:
   - `production`
   - `staging`
3. For `production` environment:
   - Enable **Required reviewers** (optional)
   - Add protection rules (optional)

## Step 6: Test Deployment

### Manual Test

Trigger a manual deployment:

1. Go to **Actions** tab in GitHub
2. Click **Deploy to Production** workflow
3. Click **Run workflow**
4. Select environment: `staging`
5. Click **Run workflow**

Watch the logs to ensure it completes successfully.

### Automatic Test

1. Make a small change to the repository
2. Create a PR and merge to `main`
3. Watch the **Actions** tab
4. Deployment should trigger automatically

## How It Works

### Automatic Deployments (Push to Main)

```
Code merged to main
  ↓
GitHub Actions triggers
  ↓
SSH to cPanel server
  ↓
cd /path/to/production
  ↓
git reset --hard origin/main
  ↓
scripts/deploy-all.sh production
  ↓
Site goes LIVE
```

### Manual Deployments

```
Developer clicks "Run workflow"
  ↓
Selects environment (production/staging)
  ↓
Same deployment process
  ↓
Site updated
```

## Workflow File

The deployment is configured in `.github/workflows/deploy.yml`:

```yaml
# Triggers on:
# 1. Push to main (automatic production deploy)
# 2. Manual workflow dispatch (choose staging or production)
```

## Deployment Scripts Used

The GitHub Actions workflow runs your existing deployment scripts:

- `scripts/deploy-all.sh production` - Deploys both sites to production
- `scripts/deploy-all.sh staging` - Deploys both sites to staging

These scripts:
1. Pull latest code
2. Run `composer install`
3. Enable maintenance mode
4. Run `drush deploy` (twice, with verbose output)
5. Disable maintenance mode (production only)
6. Clear cache

## Security Best Practices

### SSH Key Security

 **DO:**
- Generate separate keys for different purposes
- Use strong passphrases for keys stored on personal machines
- Rotate keys regularly (every 90-180 days)
- Remove keys immediately when no longer needed

 **DON'T:**
- Share private keys
- Use the same key for multiple servers
- Store private keys in the repository
- Use passwordless keys on personal machines

### GitHub Secrets Security

 **DO:**
- Use GitHub environments for sensitive deployments
- Enable required reviewers for production
- Audit secret access regularly
- Use different keys for staging and production

 **DON'T:**
- Echo secrets in workflow logs
- Share secrets between repositories unnecessarily
- Use overly permissive SSH keys

### Server Security

 **DO:**
- Restrict SSH key to specific commands if possible
- Monitor deployment logs
- Keep backup before auto-deployment
- Test on staging before production

## Troubleshooting

### SSH Connection Fails

```
Permission denied (publickey)
```

**Solutions:**
1. Verify public key is in `~/.ssh/authorized_keys` on server
2. Check file permissions: `chmod 600 ~/.ssh/authorized_keys`
3. Verify private key is correctly added to GitHub secrets
4. Check `DEPLOY_USER` and `DEPLOY_HOST` are correct

### Git Reset Fails

```
fatal: not a git repository
```

**Solutions:**
1. Verify `DEPLOY_PATH_PROD` and `DEPLOY_PATH_STAGING` are correct
2. Ensure paths point to the repository root (not `webroot/`)
3. Check user has read/write permissions

### Deployment Script Fails

```
ERROR: Deploy 1 failed
```

**Solutions:**
1. SSH to server and run script manually to see full error
2. Check logs in `logs/deploy-*.log`
3. Verify composer and drush are accessible
4. Check database credentials

### Known Hosts Error

```
Host key verification failed
```

**Solution:**
The workflow includes `ssh-keyscan` to automatically add the host. If it persists:

1. Manually add to workflow:
```yaml
- name: Add to known hosts
  run: ssh-keyscan -H your-server.com >> ~/.ssh/known_hosts
```

## Manual Deployment (Fallback)

If automatic deployment fails, you can always deploy manually:

### SSH to Server

```bash
ssh user@your-server.com
```

### Deploy Production

```bash
cd /home/user/public_html/sahistory.org.za
git pull origin main
scripts/deploy-all.sh production
```

### Deploy Staging

```bash
cd /home/user/public_html/staging.sahistory.org.za
git pull origin main
scripts/deploy-all.sh staging
```

## Monitoring

### GitHub Actions Logs

View deployment logs:
1. Go to **Actions** tab
2. Click on the deployment run
3. Expand steps to see detailed logs

### Server Logs

View deployment logs on server:
```bash
# Latest deployment log
ls -t logs/deploy-*.log | head -1 | xargs cat

# Watch live deployment
tail -f logs/deploy-*.log
```

## Rollback

If deployment breaks production:

### Option 1: Revert via GitHub

1. Find the last working commit
2. Revert the breaking commit
3. Merge revert to main
4. Auto-deployment runs with reverted code

### Option 2: Manual Rollback

```bash
ssh user@server.com
cd /path/to/production
git log --oneline -10
git reset --hard <last-working-commit>
scripts/deploy-all.sh production
```

## Advanced: Deployment Notifications

Add Slack/Discord/Email notifications on deployment success/failure by adding notification steps to `.github/workflows/deploy.yml`.

Example with Slack:

```yaml
- name: Notify Slack
  if: always()
  uses: 8398a7/action-slack@v3
  with:
    status: ${{ job.status }}
    text: 'Deployment to production ${{ job.status }}'
  env:
    SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
```

## Cost

GitHub Actions is free for public repositories. For private repositories:
- 2,000 minutes/month free
- Each deployment takes ~2-3 minutes
- Can run ~600-1000 deployments/month free

## Summary

Once set up:
1. Merge PR to `main`
2. GitHub Actions automatically deploys
3. Production goes live in ~2-3 minutes
4. No manual SSH needed

For staging deployments, use manual workflow dispatch.
