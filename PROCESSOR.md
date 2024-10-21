# Processor
The processor uses the raw `Collector/Data/<yyyy>/<yyyymmdd>.<hhmmss>` data to
generate the `Processor/Data/<yyyy>.json` files required to display the views.

- For each reading:
  - Calculate the daily, weekly and monthly period.
  - If this data is about a new period:
    - Update the record for the previous period.
    - Initialize the record for the new period.
- Use the last reading to update the last period with the current last values.
- Register the last period being processed as a starting point for the next run.

To be able to generate the `history` and `average` fields for each record,
data for the past years is kept in memory while processing a specific year.

So each run starts with:
- determine the starting periods and load the data for those years.
- load the data for the previous 4 years.
- start processing readings from the starting point until the last reading.
