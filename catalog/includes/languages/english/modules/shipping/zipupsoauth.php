<?php

    

/*
* $Id: zipupsoauth.php
* $Loc: /includes/languages/english/modules/shipping/
*
* Name: ZipShippingUPSoAuth
* Version: 2.1.0
* Release Date: 04/22/2024
* Author: Preston Lord
* 	 phoenixaddons.com / @zipurman / plord@inetx.ca
*
* License: Released under the GNU General Public License
*
* Comments: Copyright (c) 2024: Preston Lord - @zipurman - Intricate Networks Inc.
* 
* 
*   Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
* 
*   1. Re-distributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
* 
*   2. Re-distributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
* 
*   3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
* 
*   THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
* (Packaged with Zipur Bundler v2.2.0)
*/




    const MODULE_SHIPPING_ZIP_UPS_OATH_TEXT_TITLE = 'Zipur - UPS Service (oAuth)';
    const MODULE_SHIPPING_ZIP_UPS_OATH_LANG_TEXT_TITLE = 'UPS';
    const MODULE_SHIPPING_ZIP_UPS_OATH_LANG_PACKAGES = 'Package(s)';
    const MODULE_SHIPPING_ZIP_UPS_OATH_LANG_DAY = 'Day';
    const MODULE_SHIPPING_ZIP_UPS_OATH_LANG_DAYS = 'Days';
    const MODULE_SHIPPING_ZIP_UPS_OATH_LANG_DELIVERY = 'Delivery';
    const MODULE_SHIPPING_ZIP_UPS_OATH_LANG_BUSINESS = 'Business';

    const MODULE_SHIPPING_ZIP_UPS_OATH_TEXT_DESCRIPTION = 'UPS Rating Service (oAuth) (Zipur\'s Version)<br/><a href="https://phoenixcart.org/forum/app.php/addons/free_addon/ups_shipping_module/" target="_blank">https://phoenixcart.org/forum/app.php/addons/free_addon/ups_shipping_module/</a><hr />You will need to have API clientID, API clientSecret, along with your UPS customer numbers to use this module from <a href="https://developer.ups.com/apps/">https://developer.ups.com/apps/</a> <ul><li>Login to UPS Dev Portal</li><li>Add App</li><li>I want to integrate into by business</li><li>Choose your account</li><li>Next and complete form.</li><li>Also make sure that the Rates Product has been added to your app</li><li>When asked, set your callback URL to be your own website url with https://</li><li>Note the clientID and clientSecret</li><li>Enter info into this Addon\'s Settings</li></ul><br/> <div class="alert alert-warning">REQUIRES PHP-CURL TO BE INSTALLED ON SERVER</div>';

