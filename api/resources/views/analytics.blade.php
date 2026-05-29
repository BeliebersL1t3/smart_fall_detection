@extends('layouts.app')
@section('title', 'Advanced Analytics')

@section('content')
<div class="space-y-8 mt-4">

    <div class="ui-card rounded-2xl p-6 flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold ui-title">Data Visualization & Patterns</h3>
            <p class="text-sm ui-subtitle font-medium mt-1">Analyze historical data to identify risk factors and improve caregiver response times.</p>
        </div>
        <div class="hidden md:block">
            <span class="bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 px-4 py-2 rounded-lg text-sm font-bold flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                System Active
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pt-6">

        <div class="ui-card rounded-2xl relative p-4">
            <div class="absolute -top-6 left-4 right-4 bg-gradient-to-tr from-purple-600 to-indigo-500 rounded-2xl p-4 shadow-lg shadow-indigo-500/40 h-64 flex items-center justify-center">
                <canvas id="weeklyTrendChart"></canvas>
            </div>
            <div class="pt-64 px-2">
                <h4 class="text-lg font-bold ui-title">Weekly Activity Trends</h4>
                <p class="text-sm ui-subtitle mt-1 font-medium">Comparison of Falls and SOS events over the last 7 days.</p>
            </div>
        </div>

        <div class="ui-card rounded-2xl relative p-4 mt-10 lg:mt-0">
            <div class="absolute -top-6 left-4 right-4 bg-gradient-to-tr from-emerald-500 to-teal-400 rounded-2xl p-4 shadow-lg shadow-teal-500/40 h-64 flex items-center justify-center">
                <canvas id="hourlyRiskChart"></canvas>
            </div>
            <div class="pt-64 px-2">
                <h4 class="text-lg font-bold ui-title">Risk Level by Hour</h4>
                <p class="text-sm ui-subtitle mt-1 font-medium">Identifies the most dangerous hours of the day for the user.</p>
            </div>
        </div>

        <div class="ui-card rounded-2xl relative p-4 lg:col-span-2 mt-10 lg:mt-6">
            <div class="absolute -top-6 left-4 right-4 bg-gradient-to-tr from-rose-500 to-orange-400 rounded-2xl p-4 shadow-lg shadow-rose-500/40 h-80 flex items-center justify-center">
                <canvas id="impactScatterChart"></canvas>
            </div>
            <div class="pt-[21rem] px-2 pb-2">
                <h4 class="text-lg font-bold ui-title">Impact Magnitude Distribution</h4>
                <p class="text-sm ui-subtitle mt-1 font-medium">Tracks the intensity (G-Force) of falls over time to detect worsening conditions.</p>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    const days = {!! json_encode($days ?? []) !!};
    const hoursData = {!! json_encode($hours ?? []) !!};
    const impactData = {!! json_encode($impactData ?? []) !!};

    document.addEventListener('DOMContentLoaded', function() {
        Chart.defaults.color = 'rgba(255, 255, 255, 0.9)';
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

        const gridOptions = {
            color: 'rgba(255, 255, 255, 0.1)',
            borderColor: 'rgba(255, 255, 255, 0.2)'
        };

        new Chart(document.getElementById('weeklyTrendChart'), {
            type: 'line',
            data: {
                labels: days,
                datasets: [
                    {
                        label: 'Falls',
                        data: [2, 0, 1, 3, 1, 2, 1],
                        borderColor: '#ffffff',
                        backgroundColor: 'rgba(255, 255, 255, 0.2)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#ffffff'
                    },
                    {
                        label: 'SOS',
                        data: [1, 1, 0, 2, 0, 1, 0],
                        borderColor: 'rgba(255, 255, 255, 0.5)',
                        borderDash: [5, 5],
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(255, 255, 255, 0.5)'
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.8)' } },
                    y: { grid: gridOptions, ticks: { color: 'rgba(255,255,255,0.8)', stepSize: 1 } }
                },
                plugins: { legend: { position: 'top' } }
            }
        });

        new Chart(document.getElementById('hourlyRiskChart'), {
            type: 'bar',
            data: {
                labels: Array.from({length: 24}, (_, i) => i + ':00'),
                datasets: [{
                    label: 'Events Count',
                    data: Object.values(hoursData),
                    backgroundColor: 'rgba(255, 255, 255, 0.8)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.8)', maxTicksLimit: 12 } },
                    y: { grid: gridOptions, ticks: { color: 'rgba(255,255,255,0.8)', stepSize: 1 } }
                },
                plugins: { legend: { display: false } }
            }
        });

        new Chart(document.getElementById('impactScatterChart'), {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Fall Impact Intensity',
                    data: impactData,
                    backgroundColor: '#ffffff',
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: {
                        grid: gridOptions,
                        title: { display: true, text: 'G-Force', color: '#fff' },
                        ticks: { color: 'rgba(255,255,255,0.8)' }
                    },
                    x: {
                        type: 'category',
                        grid: { display: false },
                        title: { display: false },
                        ticks: { color: 'rgba(255,255,255,0.8)' }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
    });
</script>
@endpush
