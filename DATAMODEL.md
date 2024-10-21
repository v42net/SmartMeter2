# Data Model

### `Collector/Data/<yyyy>/<yyyymmdd>.<hhmmss>`
These files contain the raw data collected from the SmartMeter P1 port.
As the collector might restart uring the day, sometimes multiple files 
exist. The file's extension contains the time each file was started.

### `Processor/Data/<yyyy>.json` v1
Contains the starting electricity (E1 and E2 in kWh) and gas (G in m3) 
meter readings per day:
```
{ "<yyyymmdd>": [ <E1>, <E2>, <G> ] }
```
To keep all data related to one year together in one file, the entries
continue until the day *after* the last day of the last week of the year. 
Due to the way weeks are counted this might be January 4th of the next 
year. Obviously data for next year's days is *also* in next year's file.

### `Processor/Data/<yyyy>.json` v2
Contains the data for the daily views (365 or 366 entries per year):
```
{ "daily": { "<mmdd>": <record> } }
```
Contains the data for the weekly views (52 or 53 entries per year).
```
{ "weekly": { "<ww>": <record> } }
```
Contains the data for the monthly views (12 entries per year).
```
{ "monthly": { "<mm>": <record> } }
```

Each record contains the data about that period for both views:
- First Readings (3): E1, E2, G.
- Last Readings (3): E1, E2, G.
- Usage (2): E and G used during period.
- History (8): E and G usage for same period during previous 4 years.

To be added:
- Average (2): Total E and G usage since start of first period, 
  average of total E and G usage during the past 4 years.
