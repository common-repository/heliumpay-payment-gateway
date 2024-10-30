=== WooCommerce Heliumpay Payment Gateway ===
Contributors: Heliumpay, Saso Nikolov
Tags: HNT, helium, cryptocurrency payment, heliumpay, payment request, woocommerce
Requires PHP: 7.0
Stable tag: 1.0.6
Tested up to: 6.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Attributions: saso-heliumpay

NOT LONGER ACTIVE: Please deinstall. Take HNT (native cryptocurrency of the Helium blockchain) payments on your store using Heliumpay.

== Description ==

Accept Helium Token, the native cryptocurrency of the Helium blockchain directly on your store with the Heliumpay payment gateway for WooCommerce for mobile and desktop.
Over 420.000 Helium miner are available and increasing, so a lot of HNT customer are available.

= Take HNT payments easily and directly on your store =

The Heliumpay plugin extends WooCommerce allowing you to take payments directly on your store via Heliumpay’s API.

- No registration needed.
- No upfront costs.
- Easy to integrate.

Install, activate, add your own Helium account to the configuration.

The payments will be processed by us and then immediately transferred to your Helium wallet.

Heliumpay is a simple way to accept cryptocurrency payments online. With Heliumpay you can accept HNT directly on your store.

You can have products haven HNT as currency or choose any other supported currencies.
The basket total amount will be converted into HNT. Your customer can pay the total order in HNT automatically.

Heliumpay is offered by a **swiss company**
DigitalMove Consultants GmbH
Bösch 23
6331 Hünenberg
Switzerland

[Visit the Website for more details](https://heliumpay.net)

== Transaction fees ==

Please check out our website for the fees here: [Fees overview](https://heliumpay.net/fees)

= Why choose Heliumpay? =

Heliumpay has no setup fees, no monthly fees, no hidden costs: you only get charged when you earn money! Earnings are transferred to your Helium account on a hourly rolling basis.

We are the only and first WooCommerce payment gateway for Helium cryptocurrency HNT.

Heliumpay is secure and safe. Heliumpay does no require any registration on both sides, your and your customer. We just handle the wallet addresses to process the payment. So your privacy is protected and your data are safe with us.

= NOTE: Subscriptions not supported =
It is not possible to have a subscription product paid with HNT, because of the nature how you can pay with cryptos. The wallet of the customer cannot be accessed by any plugin without having the wallet key. So your customer is needed to confirm each payment transaction. We remove the heliumpay method from the list, if one or more products in the basket is a subscription.

= How does it works? =

Once your customer comes to the checkout, the Heliumpay option is displayed. Your customer decides to pay with HNT.

It doesn't matter in which currency the products are, they will converted into HNT.

The WooCommerce Heliumpay plugin will request a transaction on our payment gateway servers. The answer will display the current amount of HNT your customer needs to pay. Also a QR code will be displayed, so that they can pay easily and without mistakes with their Helium wallet app.

Once the transaction is confirmed (paid), you will receive a payout of the amount, conducted by the fees, to your wallet. Your WooCoomerce order will be triggered for further processing. The payout to your helium wallet address can take up to 30 minutes.

= Refund payment option =

You can set your heliumpay settings to allow refunds. Activate this options and enter the maximal days you allow refunds. The payout will be withhold by us and paid out after the refund period expires to you automatically or perform a refund. The refund will pay back the paid HNT to the buyer.

== Installation ==
* WordPress 5.0 or greater
* PHP version 7.0 or greater
* MySQL version 5.0 or greater

You can download an [older version of this gateway for older versions of WooCommerce from here](https://wordpress.org/plugins/woocommerce-gateway-heliumpay/developers/).

Please note, v1 of this gateway requires WooCommerce 3.0 and above.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of the WooCommerce Heliumpay plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “WooCommerce Heliumpay Payment Gateway” and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating, and description. Most importantly, of course, you can install it by simply clicking "Install Now", then "Activate".

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Frequently Asked Questions ==

= Does this support recurring payments, like for subscriptions? =

No, the Helium blockchain and API does not support withdrawal HNT from other Helium wallets for security reasons.

= Does this require an SSL certificate? =

Yes! In Live Mode, an SSL certificate must be installed on your site to use Heliumpay. In addition to SSL encryption, Heliumpay do not expose payment details on your purchase pages.

= Where can I find documentation? =

For help setting up and configuring, please refer to our [documentation](https://heliumpay.net/docs).

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the Plugin Forum. You can also always contact our support directly via email.

== Support ==

Write to support@heliumpay.net for support request.

== GETTING STARTED ==

- Go to the woocommerce settings and then to the "Payments" tab.
- Click on the heliumpay entry to manage the heliumpay settings.
- Enter your Helium wallet address (This wallet will receive the payout from heliumpay).
- Save the settings.

== Screenshots ==

1. **WooCommerce Payment Area** You can manage your heliumpay settings within WooCommerce payments area.
2. **WooCommerce Heliumpay Settings** Add your Helium wallet address to recieve your payouts.
3. **Frontend** The shortcode will be replaced by a form. The checks deliver different messages.
4. **Performing a Payment** Your customers will use the Helium app to scan the QR code and execute a payment.
5. **Heliumpay Waiting Screen** The confirmation of an payment can take up to few minutes.
6. **Heliumpay Order Details** Detailed information about the heliumpay. You will miss nothing.
7. **Heliumpay Webhook Request** Control in your hands. In case your server missed it, you can re-request a webhook call at any time.

== Upgrade Notice ==
= 1.0.6 =
The situation with helium getting so worse, that we have to stop the service. The plugin will no longer work. Your payouts will be executed. The service support will be stopped in 30 days. Please deinstall the plugin. Thank you.

== Changelog ==
= 1.0.6 - 2022-11-17 =
* Removing the payment option from the checkout
* Stop servicing the payments

= 1.0.5 - 2022-04-10 =
* If a partial payment arrives and the order is not on "pending", the order will be set again to status "pending"

= 1.0.4 - 2022-02-18 =
* You can now exclude Heliumpay payment option for individual products

= 1.0.3 - 2022-01-26 =
* Refund option added
* Better oder notes added

= 1.0.2 - 2022-01-17 =
* Payment option will be removed if one or more products are a subscription

= 1.0.1 - 2021-12-30 =
* Delays of payments reduced significant
* Code optimized

= 1.0.0 - 2021-10-27 =
* Changing to the new API
* Optimization of flow

= 0.0.1 - 2021-10-18 =
* Initial Public Release
