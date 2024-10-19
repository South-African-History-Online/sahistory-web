# South African History Online (SAHO)
The primary Git repository for the South African History Online (SAHO) website - sahistory.org.za

## Stack Overview
The full stack is:
- **Drupal 10.3.6** (upgraded from Drupal 8)
- **PHP 8.3.10**
- **Radix** theme framework with a custom subtheme called **"saho"**

## Development
You can run this project in a local environment using DDEV (quick setup).

### Getting Started
These instructions will get you a copy of the project up and running on your local machine for development, demo and testing purposes. See deployment for notes on how to deploy the project to staging, QA and production systems.

#### DDEV
DDEV sets up the project and necessary backing services. We use Node.js to build the frontend, which can be done both on the host machine or within the `web` Docker container created by DDEV.

##### Benefits
DDEV is fast, reliable, and we have extensive in-house experience using it during development.

##### Requirements
On your host machine, install the following:
- [Git](https://git-scm.com/)
- [Docker](https://ddev.readthedocs.io/en/stable/users/docker_installation/)
- [DDEV](https://ddev.readthedocs.io/en/stable/)

##### Setup Steps
1. Clone the project repository:
   ```sh
   git clone git@github.com:South-African-History-Online/sahistory-web.git
   ```
2. Enter the newly created project directory:
   ```sh
   cd sahistory-web
   ```
3. Install dependencies with Composer:
   ```sh
   ddev composer install
   ```
4. Run the project with DDEV - the database is available internally via SharePoint (ask a fellow developer):
   ```sh
   ddev import-db
   ```
5. Start the DDEV environment:
   ```sh
   ddev start
   ```
6. List entire DDEV project:
   ```sh
   ddev describe
   ```
7. List all DDEV commands:
   ```sh
   ddev list
   ```

##### Backend Developer Commands
For backend developers working in DDEV, follow these commands to get started and manage the environment:

1. **Start DDEV environment**: This command will start all required services:
   ```sh
   ddev start
   ```
2. **Install Composer dependencies**: Ensure all PHP packages are installed:
   ```sh
   ddev composer install
   ```
3. **Import the database**: Load the latest database snapshot for development:
   ```sh
   ddev import-db
   ```
4. **Run custom update scripts**: To apply any project-specific updates:
   ```sh
   .ddev/commands/host/local_update
   ```
5. **Access the Drupal site locally**: Access the site through the browser using:
   ```sh
   ddev describe
   ```
   This will provide the local URL and credentials.

##### Frontend Developer Commands
For frontend developers working in DDEV, follow these commands to set up and build the front end:

1. **Start DDEV environment**: Start the services required to run the project:
   ```sh
   ddev start
   ```
2. **Install Node.js dependencies**: Install required Node.js packages:
   ```sh
3. **Go to the saho theme directory**: Navigate to the saho theme directory for further steps:
   ```sh
   cd web/themes/custom/saho
   ```

   ddev ssh
   npm install
   ```
3. **Build the SCSS files**: Compile the styles using the provided build script:
   ```sh
   npm run build
   ```
4. **Watch for SCSS changes**: If making frequent changes to styles, use the watch command:
   ```sh
   npm run watch
   ```

#### DDEV Commands to Fix Issues
If you encounter issues with your project, try running these commands:

1. Update your local master branch or feature branch:
   ```sh
   git pull
   ```
2. Install the latest modules and themes:
   ```sh
   ddev composer install
   ```
3. Run the custom DDEV Drupal local update script:
   ```sh
   .ddev/commands/host/local_update
   ```
4. If the project is still broken, restart DDEV:
   ```sh
   ddev restart
   ```
5. If issues persist, try reimporting the database:
   ```sh
   ddev import-db
   ```
6. If the project is still not working, consult the project's tech lead.

### Composer
New functionality is often added through contributed, premium, or custom modules. These are installed and maintained using Composer, which is a dependency manager for PHP. This documentation on [Composer](https://getcomposer.org/doc/01-basic-usage.md) will help you get up to speed and manage dependencies efficiently.

**Note:** While developing new features or structures, do not update Composer dependencies unless necessary. Avoid running `composer update` unless it's part of the assigned task.

#### Quick Commands List
- To add a dependency to a project:
  ```sh
  composer require drupal/example_contrib_module
  ```
- To install all dependencies from `composer.json` or `composer.lock`:
  ```sh
  composer install
  ```

Changes to dependencies are written to `composer.json` and `composer.lock` in the root of our project, where they can be committed and pushed remotely.

### Drupal Configuration During Development
While developing a new feature or structure in Drupal, it's important to export the configuration of the feature you're working on. You can always check the status of the configuration synchronization using Drush:
   ```sh
   drush config:status
   ```
If you have changes to the configuration, use Drush to export these:
   ```sh
   drush cex -y
   ```
The configuration will be written to `.yml` files in `/config/sync`, from where they can be committed and pushed.

**Note:** When exporting config, check for language switches or overwriting customized configuration (such as standard Drupal emails changed through core updates).

### Radix and "saho" Subtheme
The SAHO website uses the Radix theme as the base, with a custom subtheme named "saho." The Radix theme allows for the use of components, which makes it easy to maintain consistency across the website.

For detailed information on Radix, you can visit [Radix Documentation](https://radix.trydrupal.com/radix/working-with-the-components/components-intro).

#### Working with the "saho" Subtheme
- The "saho" subtheme builds upon Radix's component-based architecture.
- When developing components, use the `components/` directory within the "saho" subtheme for better organization.
- Use Radix's built-in tools for consistent styling and JS integration.
- Theming is done by extending Radix templates within the "saho" theme directory, which keeps the customizations modular and easy to maintain.
- SCSS files are used to create styles, and they are compiled using Node.js. Ensure you run `npm install` in the theme directory to get the required dependencies.
- To build SCSS, run:
  ```sh
  npm run build
  ```

### Git Strategy & Collaboration
We use Git to facilitate collaboration and versioning of our code. Our Git strategy aims to keep things lean and straightforward, allowing for a dynamic workflow suitable for the size of the project and developer team.

#### Branch Naming Convention and Commit Messages
Branch naming follows Gitflow conventions with Novicell customizations:
- Branch names should include the issue tag (example: `SAHO-50`):
  ```
  SAHO-50--example-commit-message
  ```

#### Trunk-Based Development & Rebase
Trunk-based development is a version control management practice where developers merge small, frequent updates to a core "trunk" or master branch, helping achieve CI/CD and enhancing software delivery and team performance. You can learn more about [trunk-based development here](https://www.atlassian.com/continuous-delivery/continuous-integration/trunk-based-development).

##### Rebase in Developer Workflow
Here's an example workflow for using Git in this project:
1. Pull the latest changes from the master branch:
   ```sh
   git pull --rebase origin master
   ```
2. Create a feature branch:
   ```sh
   git switch -c my-awesome-feature-branch
   ```
3. Make changes and commit often:
   ```sh
   git add .
   ```
   ```sh
   git commit -m "My awesome comment"
   ```
4. Periodically rebase your work onto the master branch to incorporate recent changes:
   ```sh
   git checkout master
   ```
   ```sh
   git pull --rebase origin master
   ```
5. Rebase your feature branch onto the updated master branch:
   ```sh
   git checkout feature/my-awesome-feature-branch
   ```
   ```sh
   git rebase master
   ```
6. Push your branch to the remote repository:
   ```sh
   git push origin feature/my-awesome-feature-branch --force
   ```

**Note:** Always use the `--force` flag to overwrite the remote branch history when necessary. Avoid merging master into your feature branch; instead, rebase to keep the commit history clean.

#### Pull Requests & Reviews
New features should be submitted as Pull Requests (PRs) and reviewed by other developers on the team. A good PR process includes:
- Locally building the project with the new feature.
- Testing the new feature locally.
- Creating a PR including a list of added/removed features.
- Providing a short guide on how to test the feature for the reviewer.
- Communicating if the PR is blocked by another PR, decision, or action.

#### Best Practices for Collaboration
- Use only fast-forward merges to the trunk.
- Use Pull Requests for merges to engage team members (master branch is protected).
- Squash multiple commits into one for a cleaner history.
- Rebase master against your short-lived branches, but do not merge master into them.
- Feature branches should be short-lived (ideally max. two working days).
- Keep commit messages concise and always include the case number.
- Never commit secrets of any kind to the repository.
- Use the `--sort-packages` flag with Composer for better dependency management.

Let's keep the repository clean and delete old feature branches when they are no longer needed.
