# Changelog

## Version 5.0.0

First release for many years. Contains many bug fixes and stability patches so this is the most important update since.

### Breaking Changes

- PHP 7.0 is now required. PHP 5.x is no longer supported.
- Package versions:
    - illuminate/support: now supports Laravel 6.x and 7.x
    - nesbot/carbon: was ^1.18, now ^2.38
    - symfony/options-resolver: was ^3.0, now ^5.1.3
    - monolog/monolog: was ^1.19, now ^2.1
- Cache adapters such as apc, memcache and redis are no longer supported. There is no longer a `cachePool` option.
- ext-libevent is no longer supported as it only applies for PHP 5.x.
- The `Collection` class no longer extends Laravel collections.
    - As such, some functions are no longer present.
    - Feel free to add an issue if you would like to see a function added.
- Channels:
    - `Channel::setPermissions()` function now takes a role or member as well as two arrays: one array of allow permissions and one array of deny permissions.
    - `Channel::createInvite()` now takes an array of options. See the [Discord developer docs](https://discord.com/developers/docs/resources/channel#create-channel-invite) for a list of valid options.
    - Messages can no longer be created using the message repository as part of the channel. Use `Channel::sendMessage()` instead.
- Overwrites:
    - The `allow` and `deny` parameters of an overwrite are an instance of `ChannelPermission` instead of `int`.
- Guilds:
    - Removed [old region constants](https://github.com/teamreflex/DiscordPHP/blob/ca05832fa0d5700d96f5ecee2fe32a3aa6125f41/src/Discord/Parts/Guild/Guild.php). Added the `Guild::getVoiceRegions()` function to get an array of valid regions.
    - `Guild::validateRegion()` now has to perform an async HTTP request to validate the region. Only use this if nessasary.
- Removed the `Game` class. Renamed to `Activity` and new attributes added.
- `Discord::updatePresence()` now takes an `Activity` object as well as options `idle`, `status` and `afk`.

### Features

- Added `getLoop()` and `getLogger()` functions to the `Discord` client.
- Collectors:
    - Channels now have message collectors. See the phpdoc of `Channel::createMessageColletor()` for more information.
    - Messages now have reaction collectors. See the phpdoc of `Message::createReactionCollector()` for more information.
- Added the [`Reaction`](https://github.com/teamreflex/DiscordPHP/blob/ca05832fa0d5700d96f5ecee2fe32a3aa6125f41/src/Discord/Parts/Channel/Reaction.php) class.
- Added the [`Webhook`](https://github.com/teamreflex/DiscordPHP/blob/ca05832fa0d5700d96f5ecee2fe32a3aa6125f41/src/Discord/Parts/Channel/Webhook.php) class.
- Implemented gateway intents:
    - See the [`Intents` class](https://github.com/teamreflex/DiscordPHP/blob/ca05832fa0d5700d96f5ecee2fe32a3aa6125f41/src/Discord/WebSockets/Intents.php) for constants.
    - User can specify an `intents` field in the options array, containing either an array of intents or an integer corresponding to the intents.

### Changes

- WebSocket:
    - Added new events: `GUILD_INTEGRATIONS_UPDATE`, `INVITE_CREATE`, `INVITE_DELETE`, `MESSAGE_REACTION_REMOVE_EMOJI`.
    - Client will not retrieve guild bans by default anymore. Set `retrieveBans` to `true` in options to retrieve on guild availability.
- Command client:
    - Help command now prints a rich embed (#305 thanks @oliverschloebe)
    - Commands have a short and long description.
    - Commands have a cooldown option.
- Factory now has a `part()` and `repository()` function to bypass `strpos` functions.
- Channels:
    - [Added new attributes](https://github.com/teamreflex/DiscordPHP/pull/309/files#diff-d1f173f4572644420fb9cd5d0b540c59R51-R58).
    - [Added new channel types](https://github.com/teamreflex/DiscordPHP/pull/309/files#diff-d1f173f4572644420fb9cd5d0b540c59R66-R72).
    - Added webhook classes and repositories.
    - `Channel::setOverwrite()` has been added to perform the action of `setPermissions()` from the previous version.
- Messages:
    - [Added new attributes](https://github.com/teamreflex/DiscordPHP/pull/309/files#diff-dcdab880a1ed5dbd0b65000834e4955cR44-R55).
    - [Added new message types](https://github.com/teamreflex/DiscordPHP/pull/309/files#diff-dcdab880a1ed5dbd0b65000834e4955cR59-R78).
    - Added `Message::delayedReply()` to perform a reply after a specified duration.
    - `Message::react()` and `Message::deleteReaction()` now takes an `Emoji` object or a string emoji.
    - Added `Message::delete()` to delete a message without using the repository.
    - Added `Message::addEmbed()` to add an embed to the message.
    - Added the [`MessageReaction` class](https://github.com/teamreflex/DiscordPHP/blob/ca05832fa0d5700d96f5ecee2fe32a3aa6125f41/src/Discord/Parts/WebSockets/MessageReaction.php) to represent a reaction to a message.
- Embeds:
    - Added the `type` parameter.
- Emojis:
    - Added the `animated` parameter.
    - Added the `Emoji::toReactionString()` function to convert to a format to put in a `Reaction` object.
    - Added the `Emoji::__toString()` object for sending emojis in messages.
- Guilds:
    - Guild region is no longer checked before saving. Make sure to handle any exceptions from Discord servers and do not spam.
    - Roles can now update their `mentionable` attribute.
- Permissions:
    - [Added new permissions.](https://github.com/teamreflex/DiscordPHP/pull/309/files#diff-60e83a1d96a4957061230b770a056001R5-R35)
- Members:
    - [Added new attributes.](https://github.com/teamreflex/DiscordPHP/pull/309/files#diff-8f236f99fe6eec45c56cff1be0ba0f90R40-R42)
    - The `game` attribute now returns an `Activity` part.
- Presence updates:
    - [Added new attributes.](https://github.com/teamreflex/DiscordPHP/pull/309/files#diff-d6e13d509fb506d128c564d3ea4217adR25-R32)
- Typing updates:
    - [Added new attributes.](https://github.com/teamreflex/DiscordPHP/pull/309/files#diff-bc4d0e1ce4e436c29b922dd26266df68R26-R32)
- Voice state updates:
    - [Added new attributes.](https://github.com/teamreflex/DiscordPHP/pull/309/files#diff-4aa18d683d39063927ff9ff28149698fR21-R35)

### Bug Fixes

- Improved memory usage by removing `resolve` and `reject` functions from `Part`s.
    - Memory leak has been improved but is still preset.
- `AbstractRepository::freshen()` now actually freshens the part, rather than being cached.
- Voice client has been updated to use the correct UDP server given by the web socket.
- Events *should* update their corresponding repositories more consistently.
- Improved the processing speed of `GUILD_CREATE` and `GUILD_MEMBERS_CHUNK` events.
- Added new gateway operation and close codes.
- Client will not attempt to reconnect to Discord servers if it receives a "critical" opcode (one that cannot be resolved by the bot).