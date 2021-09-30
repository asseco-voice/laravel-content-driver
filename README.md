<p align="center"><a href="https://see.asseco.com" target="_blank"><img src="https://github.com/asseco-voice/art/blob/main/evil_logo.png" width="500"></a></p>

# Content driver

The purpose of this repository is to provide a driver for the Content service.

## Installation

Require the package with ``composer require asseco-voice/laravel-content-driver``.
Service provider will be registered automatically.

## Setup

In order to use this repository the following variables must be se in your ``.env`` file:

1. ``FILESYSTEM_DRIVER`` - sets the driver that will be used. Must be set to ``content``
2. ``CONTENT_ROOT`` - directory that will be treated as the root directory for your app. By default, 
the snake cased name of your app will be used
3. ``CONTENT_API_URL`` - base URL of the Content service
4. ``CONTENT_REPOSITORY`` - sets the default repository

Example:

```
FILESYSTEM_DRIVER=content
CONTENT_ROOT=contacts
CONTENT_API_URL=https://example/v1/content
CONTENT_REPOSITORY=dms
```