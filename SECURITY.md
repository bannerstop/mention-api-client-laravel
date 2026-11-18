# Security policy

## Reporting a vulnerability

Please do not open a public issue for security problems. Report them privately,
either by e-mail to [dev@bannerstop.com](mailto:dev@bannerstop.com) or through
GitHub: open the **Security** tab of this repository and choose **Report a
vulnerability**. We will get back to you and keep you posted until a fix is
released.

## Supported versions

There is one major version for all supported PHP and Laravel versions (see the
README). Security fixes go into the latest minor release of the current major
version.

Laravel 8 to 10 no longer receive security fixes from the Laravel team. The
package runs on them, but whether your application is safe on them is for you
to decide; upgrade Laravel when you can.

## What the package does

- The API key and the client secret are read from the configuration only; set
  them through environment variables or your secret store, never in a file under
  version control.
- Nothing is cached except the access token of client credentials, in the cache
  store you configure.
- The generator command only writes below the directory you name and only
  replaces files that it generated itself.

Authentication, error handling and retries live in the core package
[bannerstop/mention-api-client](https://github.com/bannerstop/mention-api-client);
its [security policy](https://github.com/bannerstop/mention-api-client/blob/main/SECURITY.md)
describes what it guarantees.

## What you have to do

- Call mention-api and the token endpoint over HTTPS only.
- Use a cache store that only your application can read, because it holds access
  tokens.
- Keep the generated classes under review like any other code you commit.
