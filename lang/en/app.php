<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used throughout the application for
    | notifications, flash messages, and custom validation rules.
    |
    */

    // Notifications - Invitation
    "You've been invited to join :congregationName" => "You've been invited to join :congregationName",
    'Hello :name,' => 'Hello :name,',
    "You've been invited to join :congregationName as :role." => "You've been invited to join :congregationName as :role.",
    'Accept Invitation' => 'Accept Invitation',
    'This invitation expires in 72 hours.' => 'This invitation expires in 72 hours.',
    'If you did not expect this invitation, you can ignore this email.' => 'If you did not expect this invitation, you can ignore this email.',

    // Notifications - Booking Deleted
    'Your booking ":bookingName" has been deleted' => 'Your booking ":bookingName" has been deleted',
    'Your booking has been deleted by another user.' => 'Your booking has been deleted by another user.',
    '**Booking:** :bookingName' => '**Booking:** :bookingName',
    '**Time:** :startsAt – :endsAt' => '**Time:** :startsAt – :endsAt',
    '**Rooms:** :rooms' => '**Rooms:** :rooms',
    '**Deleted by:** :name (:role)' => '**Deleted by:** :name (:role)',
    '**Deleted at:** :timestamp' => '**Deleted at:** :timestamp',

    // Notifications - Booking Modified
    'Your booking ":bookingName" has been modified' => 'Your booking ":bookingName" has been modified',
    ':modifierName (:modifierRole) modified your booking.' => ':modifierName (:modifierRole) modified your booking.',
    '**Previous time:** :oldTimeRange' => '**Previous time:** :oldTimeRange',
    '**New time:** :newTimeRange' => '**New time:** :newTimeRange',
    '**Previous rooms:** :oldRooms' => '**Previous rooms:** :oldRooms',
    '**New rooms:** :newRooms' => '**New rooms:** :newRooms',
    '**Changed at:** :actionTime' => '**Changed at:** :actionTime',

    // Flash messages - Congregation
    'Congregation updated.' => 'Congregation updated.',
    'Congregation color updated.' => 'Congregation color updated.',
    'Congregation moved successfully.' => 'Congregation moved successfully.',
    'Congregation deleted.' => 'Congregation deleted.',
    'Congregation added.' => 'Congregation added.',

    // Flash messages - Kingdom Hall & Rooms
    'Kingdom Hall updated.' => 'Kingdom Hall updated.',
    'Kingdom Hall deleted.' => 'Kingdom Hall deleted.',
    'Room created.' => 'Room created.',
    'Room renamed.' => 'Room renamed.',
    'Room deleted.' => 'Room deleted.',

    // Flash messages - Members & Invitations
    'Invitation sent.' => 'Invitation sent.',
    'Invitation sent successfully.' => 'Invitation sent successfully.',
    'Invitation cancelled.' => 'Invitation cancelled.',
    'Failed to send invitation. Please try again.' => 'Failed to send invitation. Please try again.',
    'Member role updated.' => 'Member role updated.',
    'Member removed.' => 'Member removed.',
    'Member removed from congregation.' => 'Member removed from congregation.',

    // Flash messages - Settings
    'Profile updated.' => 'Profile updated.',
    'Password updated.' => 'Password updated.',
    'Other sessions terminated.' => 'Other sessions terminated.',

    // Flash messages - Teams (underlying infrastructure)
    'Team created.' => 'Team created.',
    'Team updated.' => 'Team updated.',
    'Team deleted.' => 'Team deleted.',

    // Custom validation rules
    'A room with this name already exists in the Kingdom Hall.' => 'A room with this name already exists in the Kingdom Hall.',
    'This user is already a member of the team.' => 'This user is already a member of the team.',
    'An invitation has already been sent to this email address.' => 'An invitation has already been sent to this email address.',
    'This team name is reserved and cannot be used.' => 'This team name is reserved and cannot be used.',
    'This invitation was sent to a different email address.' => 'This invitation was sent to a different email address.',
    'This invitation has already been accepted.' => 'This invitation has already been accepted.',
    'This invitation has expired.' => 'This invitation has expired.',
    'The team name does not match.' => 'The team name does not match.',
    'The team owner cannot be removed.' => 'The team owner cannot be removed.',
    'This member cannot be removed because no other active members exist to receive their bookings. Remove their future bookings first or add another member.' => 'This member cannot be removed because no other active members exist to receive their bookings. Remove their future bookings first or add another member.',
    'The congregation number must contain only digits and uppercase letters (A–Z).' => 'The congregation number must contain only digits and uppercase letters (A–Z).',
    'This congregation number is already in use.' => 'This congregation number is already in use.',
    'This email address is already in use.' => 'This email address is already in use.',
    'The color must be a valid hex color (e.g., #3B82F6).' => 'The color must be a valid hex color (e.g., #3B82F6).',
    'This color is too similar to another congregation\'s color in this Kingdom Hall.' => 'This color is too similar to another congregation\'s color in this Kingdom Hall.',
    'Unable to generate a distinct color. The Kingdom Hall may have too many congregations with similar colors.' => 'Unable to generate a distinct color. The Kingdom Hall may have too many congregations with similar colors.',
    'Cannot move congregation: unable to generate a distinct color for the destination Kingdom Hall.' => 'Cannot move congregation: unable to generate a distinct color for the destination Kingdom Hall.',
    'This congregation is not currently assigned to a Kingdom Hall.' => 'This congregation is not currently assigned to a Kingdom Hall.',
    'The target Kingdom Hall is the same as the current one.' => 'The target Kingdom Hall is the same as the current one.',
    'Room :number' => 'Room :number',

    // Invitation acceptance
    'Please log in to accept this invitation.' => 'Please log in to accept this invitation.',
    'An account with this email already exists. Please log in.' => 'An account with this email already exists. Please log in.',

    // Notifications - Teams
    "You've been invited to join :teamName" => "You've been invited to join :teamName",
    ':inviterName has invited you to join the :teamName team.' => ':inviterName has invited you to join the :teamName team.',
    'Accept invitation' => 'Accept invitation',

    // General
    'All rights reserved.' => 'All rights reserved.',
    'Deleted user' => 'Deleted user',

];
