UPGRADE
=======

# Versions history

## 3.5.1 (2018050801)
## 3.6.1 (2018112801)
- Moodle 3.6 upgrade from 3.5.1 (2018050801)

## 3.5.2 (2018050802)
- Events & xAPI

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
- Quetzal statistics issue (#6)

## 3.5.8 (2018050808)
- Quetzal statistics issue (#6)


# Database impact

## Safeexam column
- Added to 3.5.6
- Added to 3.6.2


# Tests

## Basic usage
- Test content / Open / Current window / Unlimited attempts / Last score - OK
- Launch / Exit incomplete - OK
- Resume / Exit passed - OK
- New attempt / Exit failed - OK
- Report (failed) - OK
- Review mode - OK

## Settings
- Availability - OK
- Max time - OK
- Passing score - OK
- Display in - OK
- Display close button - OK
- Display chronometer - OK
- Number of attempts - OK
- Scoring method - OK
- Prevent new attempts after success - OK
- Display rank - OK
- Review access - OK
- Protect from session timeout - OK
- Get/set a debug file - OK
- Record error logs - OK

## Operations
- Delete attempts - OK
- Duplicate - OK
- Backup / Restore - OK

## Quetzal statistics
- Quetzal statistics - OK

## Safe Exam
- Moodle 3.9 changes !!!!!!!!!!!!!!!!!!!

## Data privacy
- Check Admin > Users > Privacy and policies > Plugin privacy registry - OK
- Run CRON - OK
- Download and explore data - OK

## Events
- Attempt completed - OK
- Attempt failed - OK
- Attempt initialized - OK
- Attempt launched - OK
- Attempt passed - OK
- Attempt terminated - OK
- Course module viewed - OK
- Course module instance list viewed
- SCO result forced
- SCO result reset
- SCO result updated - OK
- User graded - OK

## xAPI

### Sync
- Attempt completed - OK
- Attempt failed - OK
- Attempt initialized - OK
- Attempt launched - OK
- Attempt passed - OK
- Attempt terminated - OK
- Course module viewed - OK
- Course module completion updated - OK
- SCO result forced - OK
- SCO result reset - OK
- SCO result updated - OK
- User graded - OK

### Async
Errors with Trax Logs which has not been upgraded yet !!!!!!!!!!!!!!!!!!!!!!

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
