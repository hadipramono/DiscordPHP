<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class MessageReactionAdd extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data)
    {
        $reaction = $this->factory->create(MessageReaction::class, $data, true);
        $deferred->resolve($reaction);
    }
}
