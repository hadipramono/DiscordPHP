<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Carbon\Carbon;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Repository\Guild as Repository;
use Illuminate\Support\Collection;
use React\Promise\Deferred;

/**
 * A Guild is Discord's equivalent of a server. It contains all the Members, Channels, Roles, Bans etc.
 *
 * @property string                   $id                 The unique identifier of the guild.
 * @property string                   $name               The name of the guild.
 * @property string                   $icon               The URL to the guild icon.
 * @property string                   $icon_hash          The icon hash for the guild.
 * @property string                   $region             The region the guild's voice channels are hosted in.
 * @property \Discord\Parts\User\User $owner              The owner of the guild.
 * @property string                   $owner_id           The unique identifier of the owner of the guild.
 * @property Carbon                   $joined_at          A timestamp of when the current user joined the guild.
 * @property string                   $afk_channel_id     The unique identifier of the AFK channel ID.
 * @property int                      $afk_timeout        How long you will remain in the voice channel until you are moved into the AFK channel.
 * @property bool                     $embed_enabled      Whether the embed is enabled.
 * @property string                   $embed_channel_id   The unique identifier of the channel that will be used for the embed.
 * @property array[string]            $features           An array of features that the guild has.
 * @property string                   $splash             The URL to the guild splash.
 * @property string                   $splash_hash        The splash hash for the guild.
 * @property bool                     $large              Whether the guild is considered 'large' (over 250 members).
 * @property int                      $verification_level The verification level used for the guild.
 * @property int                      $member_count       How many members are in the guild.
 * @property \Discord\Repository\Guild\RoleRepository    $roles
 * @property \Discord\Repository\Guild\ChannelRepository $channels
 * @property \Discord\Repository\Guild\MemberRepository  $members
 * @property \Discord\Repository\Guild\InviteRepository  $invites
 * @property \Discord\Repository\Guild\BanRepository     $bans
 * @property \Discord\Repository\Guild\EmojiRepository   $emojis
 */
class Guild extends Part
{
    const REGION_DEFAULT = 'us_west';

    /**
     * The 'off' verification level.
     *
     * @var int Raw verification level.
     */
    const LEVEL_OFF = 0;

    /**
     * The 'low' verification level.
     *
     * Members must have a verified email before they can message.
     *
     * @var int Raw verification level.
     */
    const LEVEL_LOW = 1;

    /**
     * The 'medium' verification level.
     *
     * Members must also be registered on Discord for more than 5 minutes.
     *
     * @var int Raw verification level.
     */
    const LEVEL_MEDIUM = 2;

    /**
     * The 'tableflip' verification level.
     *
     * Members must also be a member of the guild for more than 10 minutes.
     *
     * @var int Raw verification level.
     */
    const LEVEL_TABLEFLIP = 3;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'name',
        'icon',
        'region',
        'owner_id',
        'roles',
        'joined_at',
        'afk_channel_id',
        'afk_timeout',
        'embed_enabled',
        'embed_channel_id',
        'features',
        'splash',
        'emojis',
        'large',
        'verification_level',
        'member_count',
    ];

    /**
     * {@inheritdoc}
     */
    protected $repositories = [
        'members' => Repository\MemberRepository::class,
        'roles' => Repository\RoleRepository::class,
        'channels' => Repository\ChannelRepository::class,
        'bans' => Repository\BanRepository::class,
        'invites' => Repository\InviteRepository::class,
        'emojis' => Repository\EmojiRepository::class,
    ];

    /**
     * An array of valid regions.
     *
     * @var Collection|null
     */
    protected $regions;

    /**
     * Gets the voice regions available.
     *
     * @return \React\Promise\Promise
     */
    public function getVoiceRegions()
    {
        $deferred = new Deferred();

        $this->http->get('voice/regions')->then(function ($regions) use ($deferred) {
            $regions = new Collection($regions);

            $this->regions = $regions;
            $deferred->resolve($regions);
        }, \React\Partial\bind([$deferred, 'reject']));

        return $deferred->promise();
    }

    /**
     * Creates a role.
     *
     * @param array $data The data to fill the role with.
     *
     * @return \React\Promise\Promise
     */
    public function createRole(array $data = [])
    {
        $deferred = new Deferred();

        $rolePart = $this->factory->create(Role::class);

        $this->roles->save($rolePart)->then(
            function ($role) use ($deferred, $data) {
                $role->fill($data);

                $this->roles->save($role)->then(
                    function ($role) use ($deferred) {
                        $deferred->resolve($role);
                    },
                    \React\Partial\bind([$deferred, 'reject'])
                );
            },
            \React\Partial\bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Transfers ownership of the guild to
     * another member.
     *
     * @param Member|int $member The member to transfer ownership to.
     *
     * @return \React\Promise\Promise
     */
    public function transferOwnership($member)
    {
        $deferred = new Deferred();

        if ($member instanceof Member) {
            $member = $member->id;
        }

        $this->http->patch(
            $this->replaceWithVariables('guilds/:id'),
            [
                'owner_id' => $member,
            ]
        )->then(
            function ($response) use ($member, $deferred) {
                if ($response->owner_id != $member) {
                    $deferred->reject(new \Exception('Ownership was not transferred correctly.'));
                    $this->fill((array) $response);
                } else {
                    $deferred->resolve();
                }
            },
            \React\Partial\bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Returns the channels invites.
     *
     * @return \React\Promise\Promise
     */
    public function getInvites()
    {
        $deferred = new Deferred();

        $this->http->get($this->replaceWithVariables('guilds/:id/invites'))->then(
            function ($response) use ($deferred) {
                $invites = new Collection();

                foreach ($response as $invite) {
                    $invite = $this->factory->create(Invite::class, $invite, true);
                    $invites->push($invite);
                }

                $deferred->resolve($invites);
            },
            \React\Partial\bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Returns the owner.
     *
     * @return \React\Promise\Promise
     */
    public function getOwnerAttribute()
    {
        return $this->discord->users->get('id', $this->owner_id);
    }

    /**
     * Returns the joined_at attribute.
     *
     * @return Carbon|null The joined_at attribute.
     */
    public function getJoinedAtAttribute()
    {
        if (! array_key_exists('joined_at', $this->attributes)) {
            return;
        }

        return new Carbon($this->attributes['joined_at']);
    }

    /**
     * Returns the guilds icon.
     *
     * @param string $format The image format.
     * @param int    $size   The size of the image.
     *
     * @return string|null The URL to the guild icon or null.
     */
    public function getIconAttribute($format = 'jpg', $size = 1024)
    {
        if (is_null($this->attributes['icon'])) {
            return;
        }

        if (false === array_search($format, ['png', 'jpg', 'webp'])) {
            $format = 'jpg';
        }

        return "https://cdn.discordapp.com/icons/{$this->id}/{$this->attributes['icon']}.{$format}?size={$size}";
    }

    /**
     * Returns the guild icon hash.
     *
     * @return string|null The guild icon hash or null.
     */
    public function getIconHashAttribute()
    {
        return $this->attributes['icon'];
    }

    /**
     * Returns the guild splash.
     *
     * @param string $format The image format.
     * @param int    $size   The size of the image.
     *
     * @return string|null The URL to the guild splash or null.
     */
    public function getSplashAttribute($format = 'jpg', $size = 2048)
    {
        if (is_null($this->attributes['splash'])) {
            return;
        }

        if (false === array_search($format, ['png', 'jpg', 'webp'])) {
            $format = 'jpg';
        }

        return "https://cdn.discordapp.com/slashes/{$this->id}/{$this->attributes['splash']}.{$format}?size={$size}";
    }

    /**
     * Returns the guild splash hash.
     *
     * @return string|null The guild splash hash or null.
     */
    public function getSplashHashAttribute()
    {
        return $this->attributes['splash'];
    }

    /**
     * Validates the specified region.
     *
     * @return \React\Promise\Promise
     *
     * @see self::REGION_DEFAULT The default region.
     */
    public function validateRegion()
    {
        $deferred = new Deferred();

        $validate = function () use ($deferred) {
            $regions = $this->regions->map(function ($region) {
                return $region->id;
            })->toArray();
            
            if (! in_array($this->region, $regions)) {
                $deferred->resolve(self::REGION_DEFAULT);
            } else {
                $deferred->resolve($this->region);
            }
        };

        if (! is_null($this->regions)) {
            $validate();
        } else {
            $this->getVoiceRegions()->then($validate, \React\Partial\bind([$deferred, 'reject']));
        }

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes()
    {
        return [
            'name' => $this->name,
            'region' => $this->region,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        return [
            'name' => $this->name,
            'region' => $this->region,
            'logo' => $this->logo,
            'splash' => $this->splash,
            'verification_level' => $this->verification_level,
            'afk_channel_id' => $this->afk_channel_id,
            'afk_timeout' => $this->afk_timeout,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryAttributes()
    {
        return [
            'guild_id' => $this->id,
        ];
    }
}
