# Proposal: Database Records Archiving Project

## Introduction

In an effort to improve the efficiency and performance of the Rose Voucher Scheme's database system, we propose the implementation of a records archiving project. Over time, the database has accumulated a significant amount of historical data that is rarely accessed but consumes valuable storage and processing resources; in particular during reporting operations. Archiving old records will help us optimise database performance while maintaining data integrity and availability.

## Objectives

The primary objectives of this project are as follows:

1. **Identify and Define Archiving Criteria:** Determine the criteria for selecting records to be archived. This may include date ranges, data categories, or other relevant factors.

2. **Develop Archiving Process:** Establish a clear and efficient process for moving selected records from the production database to an archival storage system.

3. **Data Backup and Recovery:** Ensure that the archived data is backed up securely and can be easily recovered if needed.

4. **Data Retention Policies:** Define data retention policies that specify how long data will be archived before it is permanently deleted.

5. **Security and Compliance:** Ensure that archived data is stored securely and in compliance with any relevant regulations, such as GDPR or HIPAA.

6. **Documentation:** Maintain comprehensive documentation of the archiving process for future reference.

## Methodology

The following steps will be taken to achieve the objectives:

1. **Review Existing Data**: Analyze the database to identify data that is suitable for archiving. Vouchers that were archived more than 12 months old and were in the reimbursed state are considered finished.

2. **Archiving Process Design**: A snapshot containing a time stamped copy of the database will be taken along with a record of the software version at that point. This would allow the entire system to be recreated, precisely as it was at any date at any date, at any time in the future, should detailed analysis be needed.

3. **Archive Storage Selection**: Each snapshot would be shared with ARC and stored on the Neontribe backup server.

4. **Data Backup Strategy**: Backups would be conducted each month as part of the monthly maintenance and security sweep.

5. **Data Retention Policies**: Monthly backups will be stored for the proceeding 12 months, and a single backup stored for each financial year.

6. **Security and Compliance Measures**: The snapshots will all be password protected. The password will be rotated each year and stored in a secure password manager. 

7. **Documentation**: The backup, retention, security and any other pertinent details will be stored as files in the ARC server git repository.

## Conclusion

Archiving old records from our database is a crucial step towards optimising database performance and reducing server costs. By implementing this project, we can ensure that our database remains efficient, compliant, and secure, while also adhering to data retention policies and industry good practices.
