<?php

namespace ProjectInfinity\PocketBans\permission;

abstract class Permissions {
    # Admin permissions
    const ADMIN         = 'pocketbans.admin';
    const VIEW_ALTS     = 'pocketbans.admin.view.alts';
    const VIEW_BANS     = 'pocketbans.admin.view.bans';
    const VIEW_STAFF    = 'pocketbans.admin.view.staff';
    const VIEW_PROXY    = 'pocketbans.admin.view.proxy';
    const VIEW_ANNOUNCE = 'pocketbans.admin.view.announce';

    # Ban permissions
    const KICK          = 'pocketbans.kick';
    const KICK_ALL      = 'pocketbans.kick.all';
    const BAN_LOCAL     = 'pocketbans.ban.local';
    const BAN_GLOBAL    = 'pocketbans.ban.global';
    const BAN_TEMP      = 'pocketbans.ban.temp';
    const BAN_IP        = 'pocketbans.ban.ip';
    const UNBAN         = 'pocketbans.ban.unban';

    # Exempts
    const EXEMPT_KICK   = 'pocketbans.exempt.kick';
    const EXEMPT_BAN    = 'pocketbans.exempt.ban';
    const EXEMPT_ALTS   = 'pocketbans.exempt.alts';

    # Lookups
    const LOOKUP_PLAYER = 'pocketbans.lookup.player';
    const LOOKUP_BAN    = 'pocketbans.lookup.ban';
    const LOOKUP_ALT    = 'pocketbans.lookup.alt';
}