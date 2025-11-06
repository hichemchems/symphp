# TODO: Fix Dashboard Data Issues

## Issues Identified
- Employee dashboard shows only 10 services instead of 14 (limited to 10 in code).
- Missing commission and HT price figures in employee dashboard.
- Admin employee card shows correct client count (14) but same figures as employee dashboard.
- Admin/revenues shows 14 clients correctly.
- Commission validation not working properly.
- After validation, commission calculation should reset to 0 (by excluding validated commissions from total).

## Steps to Fix
1. Remove limit on revenues in EmployeeDashboardController (show all revenues).
2. Change totalCommission calculation to sum from unvalidated weekly commissions.
3. Add getEmployeeStats method in AdminDashboardController.
4. Pass allRevenues and validatedCommissions to employee details template.
5. Ensure validation endpoint is called from frontend.
6. Test dashboards after changes.
7. Run migrations if needed.
