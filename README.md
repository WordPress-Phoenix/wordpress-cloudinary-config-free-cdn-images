# Cloudinary (config-free) CDN Images WordPress Plugin
WordPress plugin that simplifies the use of cloudinary on your site as an image CDN using as needed image fetching.

## Installation
- Register for a [Cloudinary Account](http://cloudinary.com/invites/lpov9zyyucivvxsnalc5/zm41jatc7d1qufgtlnna) if you don't already have one. Regardless, you will need to note your "Cloud Name" for use with this plugin.
- Clone the repository into your plugins folder OR download the Zip from Github, extract the zip and rename the folder thats extracted to "sm-cloudinary-config-free-cdn-images".
- Upload the folder from your local "plugins" into your remote server.
- Login to your WordPress site, goto plugins, find and activate the plugin.
- First activiation you will be redirected to wp-admin -> settings -> media screen, input your Cloudinary Cloud Name. If you don't remember it, you can login to your account and see it on the [Cloudinary Dashboard](https://cloudinary.com/console)
- OPTIONAL: Goto your cloudinary upload settings page at [https://cloudinary.com/console/settings/upload](https://cloudinary.com/console/settings/upload) and configure auto upload mapping. For more details, visit the KB article at http://cloudinary.com/blog/how_to_automatically_migrate_all_your_images_to_the_cloud and find the section about "Setting your automatic image uploading"
- OPTIONAL: Confirm the plugin is working properly. Visit your homepage (which hopefully has some images on it...) then visit your Cloudinary.com, logging into your account, and you should see the images from your site, loaded up into your media manager.

## Why Use Cloudinary with this Plugin?

When used on WinterIsComing.net, without making any other changes, it reduced pageload speed of the GoT homepage by 1 full second. The only difference is the use of Cloudinary as a proxy for images. Cloudinary doesn't just serve as a CDN though, they optimize images and serve difference sizes "on the fly" in the best format possible for your browser to minimize bandwidth and maximize performance. 

Watch this video: 
http://www.webpagetest.org/video/view.php?id=150623_02657f2d04fe9ca6f24ca7ca845fa7c1f034c6e9

Next question, whats it cost? It operates on a "freemium" model. So you can start for free, but if you think your site is going to go over the limits of the free account, do note the very high entry cost of ~$50 a month just to get anything bigger. Is there a risk? With this plugin, no. To move away from using Cloudinary, just disable the plugin and your site goes back to serving the images from your hosting company, unoptimized.

Another fun fact is that it has facial recognition. We built "cropping to faces" as the default in this plugin, so you get this feature automatically. Here is an example where face recognition really helps make your thumbnails useful instead of providing users a bad user experience.

Crop typically made by WordPress:

http://res.cloudinary.com/demo/image/upload/w_150,h_150,c_thumb/face_top.jpg

![bad_crop](http://res.cloudinary.com/demo/image/upload/w_150,h_150,c_thumb/face_top.jpg)

Crop made by face-recog  with Cloudinary:

http://res.cloudinary.com/demo/image/upload/w_100,h_100,c_thumb,g_faces/face_top.jpg

![good_crop](http://res.cloudinary.com/demo/image/upload/w_100,h_100,c_thumb,g_faces/face_top.jpg)
