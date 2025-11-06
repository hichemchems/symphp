# TODO: Fix Dashboard Data Issues

## Issues Identified:
1. Employee dashboard shows only 10 recent revenues, but stats show 14 clients. Need to show all revenues in the list.
2. Missing commission per service in the employee dashboard revenue list.
3. Employee dashboard cards don't show client count.
4. Commission calculation doesn't reset after validation; it should be pending commissions (unvalidated).
5. No list of validations in admin employee details.
6. Validation may not work due to URL or other issues.
7. Admin dashboard shows only 10 recent appointments, but user mentions 14 clients in revenues/packages list.

## Tasks:
- [x] Fix EmployeeDashboardController to show all revenues instead of limiting to 10
- [x] Add commission column to employee dashboard revenue table
- [x] Add client count to employee dashboard stats cards
- [x] Modify commission calculation to exclude validated commissions from pending total
- [x] Add validation history list to admin employee details template
- [x] Fix commission validation URL/route issues
- [x] Increase admin dashboard recent appointments limit or show all
- [x] Update WeeklyCommissionRepository to support finding unvalidated commissions
- [x] Test all changes to ensure data consistency
