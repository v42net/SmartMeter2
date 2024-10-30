# Data Model

### `Collector/Data/<yyyy>/<yyyymmdd>.<hhmmss>`
These files contain the raw data collected from the SmartMeter P1 port.
As the collector might restart uring the day, sometimes multiple files 
exist. The file's extension contains the time each file was started.

### `Processor/Data/<yyyy>.json` v3
Contains the data for the daily views (365 or 366 entries per year):
```
{ "day": { "<mmdd>": [[<first3>],[<last3>]] } }
```
Contains the data for the weekly views (52 or 53 entries per year).
```
{ "week": { "<ww>": [[<first3>],[<last3>]] } }
```
Contains the data for the monthly views (12 entries per year).
```
{ "month": { "<mm>": [[<first3>],[<last3>]] } }
```
Each record contains the data about that period for all views:
- First Readings (3): [ E1, E2, G ]
- Last Readings (3): [ E1, E2, G ]

In addition each of these three sections contains a "0" entry
with the first and last readings of the year for that view.
