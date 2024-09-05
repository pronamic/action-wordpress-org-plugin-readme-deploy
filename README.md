# Github action WordPress.org `readme.txt` deploy

With this GitHub action you can easily deploy your WordPress plugin `readme.txt` file in the WordPress plugin directory.

## Introduction

### Streamline your WordPress plugin deployment

This GitHub action simplifies deploying your WordPress plugin's `readme.txt` file directly in the WordPress plugin directory.

### Built for WordPress developers

Leveraging PHP instead of bash scripting, this action caters to the preferences of many WordPress developers, offering a familiar and potentially more convenient workflow.

## Example

```yml
name: Deploy readme.txt to WordPress.org

on:
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest

    environment:
      name: WordPress.org plugin directory
      url: https://wordpress.org/plugins/pronamic-pay-with-mollie-for-contact-form-7/

    steps:
      - name: Checkout
        uses: actions/checkout@v4
        with:
          sparse-checkout: |
            .github
            .wordpress-org
            readme.txt

      - name: Deploy
        uses: pronamic/action-wordpress-plugin-readme-update@main
        with:
          username: pronamic
          password: ${{ secrets.SVN_PASSWORD }}
          slug: pronamic-pay-with-mollie-for-contact-form-7
```

## Inspiration

- https://github.com/marketplace/actions/wordpress-plugin-svn-deploy
  - https://github.com/nk-o/action-wordpress-plugin-deploy
- https://github.com/marketplace/actions/wordpress-plugin-readme-assets-update
  - https://github.com/10up/action-wordpress-plugin-asset-update
- https://github.com/marketplace/actions/deploy-to-wordpress-org-svn-repository
  - https://github.com/richard-muvirimi/deploy-wordpress-plugin

## Development

```
SVN_USERNAME=test SVN_PASSWORD=test WP_SLUG=salesfeed php deploy.php
```

## Links

- https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
- https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
- https://svnbook.red-bean.com/
- https://docs.github.com/en/actions/creating-actions/metadata-syntax-for-github-actions

[![Pronamic - Work with us](https://github.com/pronamic/brand-resources/blob/main/banners/pronamic-work-with-us-leaderboard-728x90%404x.png)](https://www.pronamic.eu/contact/)
