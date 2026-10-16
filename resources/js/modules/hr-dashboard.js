/**
 * HR Dashboard Analytics & Visualizations Module
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Attendance Overview Chart (Grouped / Stacked Bars for Present, Late, Absent)
    const attCanvas = document.getElementById('attendanceOverviewChart');
    if (attCanvas) {
        new Chart(attCanvas, {
            type: 'bar',
            data: {
                labels: (window.HrDashboardConfig?.past7Days || []),
                datasets: [
                    {
                        label: 'Present',
                        data: (window.HrDashboardConfig?.attSummaryPresent || []),
                        backgroundColor: '#10b981',
                        borderRadius: 5,
                        barThickness: 10,
                    },
                    {
                        label: 'Late',
                        data: (window.HrDashboardConfig?.attSummaryLate || []),
                        backgroundColor: '#f59e0b',
                        borderRadius: 5,
                        barThickness: 10,
                    },
                    {
                        label: 'Absent',
                        data: (window.HrDashboardConfig?.attSummaryAbsent || []),
                        backgroundColor: '#f43f5e',
                        borderRadius: 5,
                        barThickness: 10,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 10,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { family: 'Poppins', size: 12 },
                        bodyFont: { family: 'Poppins', size: 11 },
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.dataset.label + ': ' + context.raw + ' employees';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Poppins', size: 10.5 }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            stepSize: 2,
                            font: { family: 'Poppins', size: 10.5 },
                            color: '#64748b'
                        },
                        grid: {
                            color: '#f1f5f9',
                            borderDash: [4, 4]
                        }
                    }
                }
            }
        });
    }

    // 2. Employees by Department Donut Chart
    const deptCtx = document.getElementById('deptDonutChart');
    if (deptCtx) {
        const deptLabels = (window.HrDashboardConfig?.deptLabels || []);
        const deptCounts = (window.HrDashboardConfig?.deptCounts || []);
        const deptColors = (window.HrDashboardConfig?.deptColors || []);

        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: deptLabels,
                datasets: [{
                    data: deptCounts,
                    backgroundColor: deptColors,
                    borderWidth: 2.5,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        bodyFont: { family: 'Poppins', size: 11 },
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.label + ': ' + context.raw + ' employees';
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Payroll Summary Bar Chart with Soft Purple Gradients
    const payCanvas = document.getElementById('payrollSummaryChart');
    if (payCanvas) {
        const ctx = payCanvas.getContext('2d');
        const grossGrad = ctx.createLinearGradient(0, 0, 0, 160);
        grossGrad.addColorStop(0, '#c084fc');
        grossGrad.addColorStop(1, '#a855f7');

        const netGrad = ctx.createLinearGradient(0, 0, 0, 160);
        netGrad.addColorStop(0, '#e9d5ff');
        netGrad.addColorStop(1, '#c084fc');

        const dedGrad = ctx.createLinearGradient(0, 0, 0, 160);
        dedGrad.addColorStop(0, '#fbcfe8');
        dedGrad.addColorStop(1, '#f472b6');

        const otGrad = ctx.createLinearGradient(0, 0, 0, 160);
        otGrad.addColorStop(0, '#bae6fd');
        otGrad.addColorStop(1, '#38bdf8');

        const ndGrad = ctx.createLinearGradient(0, 0, 0, 160);
        ndGrad.addColorStop(0, '#f3e8ff');
        ndGrad.addColorStop(1, '#d8b4fe');

        const payrollData = (window.HrDashboardConfig?.payrollData || []);
        new Chart(payCanvas, {
            type: 'bar',
            data: {
                labels: ['Gross Pay', 'Total Net Pay', 'Deductions', 'Overtime Pay', 'Night Diff Pay'],
                datasets: [{
                    data: payrollData ? [
                        payrollData.total_gross,
                        payrollData.total_net,
                        payrollData.total_deductions,
                        payrollData.total_ot,
                        payrollData.total_nd
                    ] : [138250, 109439.20, 28810.80, 0, 0],
                    backgroundColor: [grossGrad, netGrad, dedGrad, otGrad, ndGrad],
                    borderRadius: 6,
                    barPercentage: 0.52,
                    categoryPercentage: 0.7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ₱' + Number(context.raw).toLocaleString('en-US', { minimumFractionDigits: 2 });
                            }
                        },
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        bodyFont: { family: 'Poppins', size: 11 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Poppins', size: 10 }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        max: 140000,
                        ticks: {
                            stepSize: 20000,
                            callback: function(val) {
                                return '₱' + (val >= 1000 ? (val / 1000) + 'k' : val);
                            },
                            font: { family: 'Poppins', size: 10 },
                            color: '#64748b'
                        },
                        grid: {
                            color: '#f1f5f9',
                            borderDash: [4, 4]
                        }
                    }
                }
            }
        });
    }

    // HR Custom Branch Dropdown Interactive Toggle
    const branchTrigger = document.getElementById('hrBranchTrigger');
    const branchMenu = document.getElementById('hrBranchMenu');

    if (branchTrigger && branchMenu) {
        branchTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = branchMenu.classList.contains('show');
            if (isOpen) {
                branchMenu.classList.remove('show');
                branchTrigger.classList.remove('active');
                branchTrigger.setAttribute('aria-expanded', 'false');
            } else {
                branchMenu.classList.add('show');
                branchTrigger.classList.add('active');
                branchTrigger.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function(e) {
            if (!branchTrigger.contains(e.target) && !branchMenu.contains(e.target)) {
                branchMenu.classList.remove('show');
                branchTrigger.classList.remove('active');
                branchTrigger.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && branchMenu.classList.contains('show')) {
                branchMenu.classList.remove('show');
                branchTrigger.classList.remove('active');
                branchTrigger.setAttribute('aria-expanded', 'false');
                branchTrigger.focus();
            }
        });
    }
});
