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
    "You've been invited to join :congregationName" => 'Du har blivit inbjuden att gå med i :congregationName',
    'Hello :name,' => 'Hej :name,',
    "You've been invited to join :congregationName as :role." => 'Du har blivit inbjuden att gå med i :congregationName som :role.',
    'Accept Invitation' => 'Acceptera inbjudan',
    'This invitation expires in 72 hours.' => 'Denna inbjudan går ut om 72 timmar.',
    'If you did not expect this invitation, you can ignore this email.' => 'Om du inte förväntade dig denna inbjudan kan du ignorera detta e-postmeddelande.',

    // Notifications - Booking Deleted
    'Your booking ":bookingName" has been deleted' => 'Din bokning ":bookingName" har tagits bort',
    'Your booking has been deleted by another user.' => 'Din bokning har tagits bort av en annan användare.',
    '**Booking:** :bookingName' => '**Bokning:** :bookingName',
    '**Time:** :startsAt – :endsAt' => '**Tid:** :startsAt – :endsAt',
    '**Rooms:** :rooms' => '**Rum:** :rooms',
    '**Deleted by:** :name (:role)' => '**Borttagen av:** :name (:role)',
    '**Deleted at:** :timestamp' => '**Borttagen:** :timestamp',

    // Notifications - Booking Modified
    'Your booking ":bookingName" has been modified' => 'Din bokning ":bookingName" har ändrats',
    ':modifierName (:modifierRole) modified your booking.' => ':modifierName (:modifierRole) ändrade din bokning.',
    '**Previous time:** :oldTimeRange' => '**Tidigare tid:** :oldTimeRange',
    '**New time:** :newTimeRange' => '**Ny tid:** :newTimeRange',
    '**Previous rooms:** :oldRooms' => '**Tidigare rum:** :oldRooms',
    '**New rooms:** :newRooms' => '**Nya rum:** :newRooms',
    '**Changed at:** :actionTime' => '**Ändrad:** :actionTime',

    // Flash messages - Congregation
    'Congregation updated.' => 'Församling uppdaterad.',
    'Congregation color updated.' => 'Församlingens färg uppdaterad.',
    'Congregation moved successfully.' => 'Församling flyttad.',
    'Congregation deleted.' => 'Församling borttagen.',
    'Congregation added.' => 'Församling tillagd.',

    // Flash messages - Kingdom Hall & Rooms
    'Kingdom Hall updated.' => 'Rikets sal uppdaterad.',
    'Kingdom Hall deleted.' => 'Rikets sal borttagen.',
    'Room created.' => 'Rum skapat.',
    'Room renamed.' => 'Rum omdöpt.',
    'Room deleted.' => 'Rum borttaget.',

    // Flash messages - Members & Invitations
    'Invitation sent.' => 'Inbjudan skickad.',
    'Invitation sent successfully.' => 'Inbjudan skickad.',
    'Invitation cancelled.' => 'Inbjudan avbruten.',
    'Failed to send invitation. Please try again.' => 'Kunde inte skicka inbjudan. Försök igen.',
    'Member role updated.' => 'Medlemsroll uppdaterad.',
    'Member removed.' => 'Medlem borttagen.',
    'Member removed from congregation.' => 'Medlem borttagen från församlingen.',

    // Flash messages - Settings
    'Profile updated.' => 'Profil uppdaterad.',
    'Password updated.' => 'Lösenord uppdaterat.',
    'Other sessions terminated.' => 'Andra sessioner avslutade.',

    // Flash messages - Teams (underlying infrastructure)
    'Team created.' => 'Team skapat.',
    'Team updated.' => 'Team uppdaterat.',
    'Team deleted.' => 'Team borttaget.',

    // Custom validation rules
    'A room with this name already exists in the Kingdom Hall.' => 'Ett rum med detta namn finns redan i Rikets sal.',
    'This user is already a member of the team.' => 'Denna användare är redan medlem i teamet.',
    'An invitation has already been sent to this email address.' => 'En inbjudan har redan skickats till denna e-postadress.',
    'This team name is reserved and cannot be used.' => 'Detta teamnamn är reserverat och kan inte användas.',
    'This invitation was sent to a different email address.' => 'Denna inbjudan skickades till en annan e-postadress.',
    'This invitation has already been accepted.' => 'Denna inbjudan har redan accepterats.',
    'This invitation has expired.' => 'Denna inbjudan har gått ut.',
    'The team name does not match.' => 'Teamnamnet stämmer inte.',
    'The team owner cannot be removed.' => 'Teamets ägare kan inte tas bort.',
    'This member cannot be removed because no other active members exist to receive their bookings. Remove their future bookings first or add another member.' => 'Denna medlem kan inte tas bort eftersom inga andra aktiva medlemmar finns för att ta emot deras bokningar. Ta bort deras framtida bokningar först eller lägg till en annan medlem.',
    'The congregation number must contain only digits and uppercase letters (A–Z).' => 'Församlingsnumret får bara innehålla siffror och versaler (A–Z).',
    'This congregation number is already in use.' => 'Detta församlingsnummer används redan.',
    'This email address is already in use.' => 'Denna e-postadress används redan.',
    'The color must be a valid hex color (e.g., #3B82F6).' => 'Färgen måste vara en giltig hexfärg (t.ex. #3B82F6).',
    'This color is too similar to another congregation\'s color in this Kingdom Hall.' => 'Denna färg liknar för mycket en annan församlings färg i denna rikets sal.',
    'Unable to generate a distinct color. The Kingdom Hall may have too many congregations with similar colors.' => 'Kunde inte generera en distinkt färg. Rikets salen kan ha för många församlingar med liknande färger.',
    'Cannot move congregation: unable to generate a distinct color for the destination Kingdom Hall.' => 'Kan inte flytta församling: kunde inte generera en distinkt färg för destinationsriksalen.',
    'This congregation is not currently assigned to a Kingdom Hall.' => 'Denna församling är inte tilldelad en rikets sal.',
    'The target Kingdom Hall is the same as the current one.' => 'Destinationsriksalen är samma som den nuvarande.',
    'Room :number' => 'Rum :number',

    // Invitation acceptance
    'Please log in to accept this invitation.' => 'Logga in för att acceptera denna inbjudan.',
    'An account with this email already exists. Please log in.' => 'Ett konto med denna e-postadress finns redan. Logga in.',

    // Notifications - Teams
    "You've been invited to join :teamName" => 'Du har blivit inbjuden att gå med i :teamName',
    ':inviterName has invited you to join the :teamName team.' => ':inviterName har bjudit in dig att gå med i teamet :teamName.',
    'Accept invitation' => 'Acceptera inbjudan',

    // General
    'All rights reserved.' => 'Alla rättigheter förbehållna.',
    'Deleted user' => 'Borttagen användare',

];
