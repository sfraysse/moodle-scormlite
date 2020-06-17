UPGRADE
=======

# Versions history

## 3.5.1 (2018050801)
## 3.6.1 (2018112801)
- Moodle 3.6 upgrade from 3.5.1 (2018050801)

## 3.5.2 (2018050802)
- Events & xAPI events

## 3.5.3 (2018050803)
- Misc issues

## 3.5.4 (2018050804)
- Solved grades issue

## 3.5.6 (2018050806)
- PHP Notice during xAPI CRON (#2)
- Player access permissions (#3)
- Safe Exam support (#5)

## 3.5.7 (2018050807)
- Reports: select user group

## 3.6.2 (2018112802)
- Get all 3.5 upgrades since 3.5.1


# Database impact

## Safeexam column
- Added to 3.5.6
- Added to 3.6.2


# Tests

## Basic usage
- Test content / Open / Current window / Unlimited attempts / Last score
- Launch / Exit incomplete
- Resume / Exit passed
- New attempt / Exit failed
- Report (failed)
- Review mode

## Settings
- Availability
- Max time
- Passing score
- Display in
- Display close button
- Display chronometer
- Number of attempts
- Scoring method
- Prevent new attempts after success
- Display rank
- Review access
- Quetzal statistics
- Protect from session timeout
- Get/set a debug file
- Record error logs

## Operations
- Duplicate / Export / Import
- Delete attempts

## Safe Exam
- Activer Safe Exam dans les paramètres expérimentaux de la plateforme.
- Utiliser un thème compatible avec le mode secure (boost, clean).
- Ne pas utiliser le mode popup.

## Data privacy

## xAPI

### Moodle events
- Attempt completed
- Attempt failed
- Attempt initialized
- Attempt launched
- Attempt passed
- Attempt terminated
- Course module viewed
- Course module instance list viewed
- SCO result forced
- SCO result reset
- SCO result updated

### Async xAPI
- Attempt completed
- Attempt failed
- Attempt initialized
- Attempt launched
- Attempt passed
- Attempt terminated
- Course module viewed
- Course module completion updated
- SCO result forced
- SCO result reset
- SCO result updated
- User graded

### Sync xAPI
- Attempt completed
- Attempt failed
- Attempt initialized
- Attempt launched
- Attempt passed
- Attempt terminated
- Course module viewed
- Course module completion updated
- SCO result forced
- SCO result reset
- SCO result updated
- User graded

