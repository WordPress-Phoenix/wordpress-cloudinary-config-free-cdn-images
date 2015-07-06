# Cloudinary (config-free) CDN Images WordPress Plugin
WordPress plugin that simplifies the use of cloudinary on your site as an image CDN using as needed image fetching.

## Installation
- Register for a [Cloudinary Account](http://cloudinary.com/invites/lpov9zyyucivvxsnalc5/zm41jatc7d1qufgtlnna) if you don't already have one
- Clone the repository into your plugins folder OR download the Zip from Github, extract the zip and rename the folder thats extracted to "sm-cloudinary-config-free-cdn-images".
- Upload the folder from your local "plugins" into your remote server.
- Login to your WordPress site, goto plugins, find and activate the plugin.
- Goto wp-admin -> settings -> cloudinary screen, input your Cloudinary username, click to enable the CDN and click save. Choose between image fetch (default config-free) or image upload (custom mapped images directories)
- Confirm the plugin is working properly. Visit your homepage (which hopefully has some images on it...) then visit your Cloudinary.com, logging into your account, and you should see the images from your site, loaded up into your media manager. 
- **UPLOAD - if using the upload option, you must configure your cloudinary account with your domain name and where you want each domains files to end up in your cloudinary account. This is useful if you have many domain names and don't want all the media in the one folder on Cloudinary.
