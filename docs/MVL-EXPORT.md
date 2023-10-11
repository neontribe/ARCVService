# Master Voucher Log Export

As voucher number grow above 3x10^6 the exporting of full voucher details is taking a long time. To allow this to be spit into smaller chunks the MVL export process can now be run in smaller units.

There are two commands, the first creates a list of vouchers that are in the state `reimbursed` and the second pulls their full data from the DB. The export process takes minutes but the processing takes up to around 20 seconds for 1000 vouchers.

## Exporting a voucher list

This will export all vouchers with a current state of reimbursed that were reimbursed in financial year 21/22. The vouchers will be placed into sequentially numbered files with no more than 50,000 vouches on each file.

```bash
./artisan arc:mvl:export --chunk-size=50000 --from=01/04/2021 --to=31/03/2022
```

This process those files. This example is the first chunk produced by the previous command. 

```bash
./artisan arc:mvl:process /home/tobias/usr/arc/service/storage/app/local/mvl/export/2023-10-10/vouchers.20210401-to-20220331.0000.txt
```

There are a couple of helper `bash` scripts to help this [here](../bin).

## Export all years

Does what it says, it exports data into one file for each financial year up to 22/23.

## Process

This takes a directory as input and processes all `.arcx` files in that directory into `.csv` files with a [deep export](https://github.com/neontribe/ARCVService/blob/develop/app/Voucher.php#:~:text=) row of fields. It also creates a file called `_headers.csv` that has the csv row headers.

After running the process commad you can concatonate the csv files into a single file using:

```bash
cat *.csv > /path/to/dest/FILENAME.csv
```

**N.B.** These csv files are processed using excel so may not contain more than 1,000,000 rows.