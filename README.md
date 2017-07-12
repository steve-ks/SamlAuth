# Kanboard SAML Authentication Plugin

Plugin for [Kanboard](https://github.com/fguillot/kanboard) :ok_hand:

This is a plugin that allows Kanboard to be used as a SP (Service Provider) and authenticate against an IDP (Identity Provider) via the SAML2 protocol.

## Instructions
Download the plugin and upload it to the /plugins directory of your Kanboard install. Then login with your admin account and fill out the required fields under **Settings** → **Integrations**.

## Certificates
Certificates can be added using the integration settings. Alternatively they should be placed within the `/var/kanboard-certs` directory.

- SP Crt: `/var/kanboard-certs/sp-public.crt`
- SP Key: `/var/kanboard-certs/sp-private.crt`
- IDP Crt: `/var/kanboard-certs/idp-public.crt`

## Contributors
* [Trajche](http://tj.mk) Kralev
