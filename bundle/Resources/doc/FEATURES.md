# Features

## Translation command

A command can Translate a Content for you

`php bin/console eztranslate [contentId] [serviceName] --from=eng-GB --to=fre-FR`


## Adding your own Remote Translation Service

This bundle enables you to provide your own Translation mechanism.

To do so, you need to:

- create a service that implements EzSystems\EzPlatformAutomatedTranslation\Client\ClientInterface
- implements the method
- tag this service: `ezplatform.automated_translation.client`  


## Logging run command
If you want to log outputs of commands processed by run command you have to add the monolog channel `eztranslate_cmd` to your configuration.

### Example
```yml
    monolog:
        channels: [...,'eztranslate_cmd']
        handlers:
          eztranslate_cmd:
            type:  stream
            path: '%kernel.logs_dir%/eztranslate_cmd.log'
            channels: ['eztranslate_cmd']
```