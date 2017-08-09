=== Plugin Name ===
Contributors: felixkoch
Donate link: http://www.felixkoch.de/easyfileshop/make-a-donation/
Tags: ecommerce, shop, e-commerce, file, download, paypal, mp3, pdf, ebook, music, software
Requires at least: 2.9.2
Tested up to: 3.0.1
Stable tag: 1.2.0

Easyfileshop enables you to sell files as downloads. Easyfileshop is easy to use and easy to integrate.

== Description ==

Easyfileshop enables you to sell files as downloads. Easyfileshop concentrates on what is realy needed for selling files as downloads. Selling files is much easier than selling t-shirts in different sizes and colors. So you don't need to learn a full featured shop system. For each post or page you may upload a file and set a price. Easyfileshop creates a fully customizable paypal checkout button for you. The files are stored inside a secure access protected folder on your server. After the payment is verified the buyer receives a customizable email with a personal download link.

Easyfileshop is easy to use and easy to integrate. You only need to edit a short settings page and if you wish customize the checkout button.

= Who could use Easyfileshop? =

* musicians, bands, record labels
* software developer
* authors
* designer
* photographer
* everybody who wants to sell downloadable products

== Installation ==

1. Upload `easyfileshop` folder to the `/wp-content/plugins/` directory.
2. Create an folder `/wp-content/easyfileshop/` on the server and make it writable (chmod 777 or less). The shop files will bes stored in this folder. An .htaccess file will be created automatically.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Open the settings page in the new toplevel 'Easyfileshop' menu.
5. Select a currency and enter your paypal email address.
6. (Opt.) You can select a return/thank you page.

= Usage =

1. Edit or create a Page or Post. Find the paragraph (metabox) `Easyfileshop` at the bottom of the edit page (admin view). Upload a file and enter a price.
2. Type the shortcode [easyfileshop] into the content of the post/page.


= Advanced Usage =

* If you have uploaded a file in a post with the id 1. And you want the same file/button in a post with the id 2, you may type the shortcode [easyfileshop id=1] in the content of post 2.
* You may also use the template tag `<?php easyfileshop(); ?>` in the loop, to retrieve the file/button of the current post.
* You may also reference the file/button of another post by referencing it over the post id: `<?php easyfileshop(1); ?>`, where 1 ist the id, where you uploaded the file.
* `<?php easyfileshop(1); ?>` does also work outside of the loop, anywhere else on the page.

*Easyfileshop degrades gracefully: If there is no file or no price or you have not entered your paypal email address, there will be no button!*

= Customization =

* Copy the file button.php into the folder `/wp-content/easyfileshop/`. Now you can edit the copied file. It will be used instead of the original button.php file and you will not loose it after an update.
* You will find the price of the file in the PHP variable $price and the currency in $currency.
* button.php will be included in the complete paypal form. Therefore it contains only the submit button. You may use a HTML submit button (e.g. `<input type="submit" value="Buy now!" />`) and style it with CSS or you can use an image submit button (e.g. `<input type="image" src="image.gif" alt="Buy now!" />`). You may change the value or alt attribute text. It does not need to be 'Buy now!'.
* If you decide to use the image button, you can use the original paypal buttons: https://www.paypal.com/newlogobuttons or any other ecommerce icons or your own image.
* If you don't need $price or $currency delete them! Only the submit button is really needed.

== Frequently Asked Questions ==

= Can I create a page with multiple products? =

Yes, see Installation / Advanced Usage.

= Can the button be customized by CSS? =

Yes, see Installation / Customization.

= When i try to buy an item, plugin connect me directly to Paypal. The plugin never ask me anything (email, name). Is this correct? = 

Yes, it is not needed. Paypal has all the payers data and will handle them to you after the checkout process. This is easy. 

= Can I sell more files than one in one checkout process? =

The plugin does not have a cart functionality. It is for cases, where you only want to sell one file at once. If you want to sell more files at once, you need to chose an other plugin.

= How often can the buyer download the file? How long is the download link valid? =

Easyfileshop allows 3 download attempts in the first 30 days after the purchase. Then the download link is not valid anymore.

= I don't get an email with the download link and the payment does not appear on the sales page. =

We have a serious problem. Maybe your wordpress installation is not reachable from outside because you are in a testing environment? Arround the "Buy Now!"-button you'll find a hidden field named notify-url. The url (ends with ...ipn.php) in the value attribute has to be available for paypal. If you open it in your browser, you shoeld see an empty white page without any error message.

== Screenshots ==

1. Sales Page, where yo can track your sales.
2. Settings Page
3. Upload/edit fields
4. A customized button

== Changelog ==

= 1.2.0 =
* Bugfix: Better handling of different payment status (in particular: pending payment).

= 1.1.0 =
* Bugfix: Shop files will not be deleted during automatic plugin update. Make sure to copy the content of your existing `/wp-content/plugins/easyfileshop/shopfiles` folder into the new folder `/wp-content/easyfileshop` folder before update!
* You can define a return/thank-you page. Your customers will be redirected to that page after checkout is completed.
* New design of the standard button (image button of paypal). No proceeding message anymore. You can customize the button and text for yourselves.

= 1.0.2 =
* Sending mails is now more robust.
* Now compatible with WP Mail SMTP.

= 1.0.1 =
* Updated readme.txt and added some screenshots.

== Upgrade Notice ==

= 1.1.0 =
IMPORTANT! Make sure to copy the content of your existing `/wp-content/plugins/easyfileshop/shopfiles` folder into the new folder `/wp-content/easyfileshop` folder before update! Otherwise you will loose all your uploaded shopfiles and customized buttons.


