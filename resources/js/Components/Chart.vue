<script setup>
import { Chart, registerables } from "chart.js";
import { BarChart } from "vue-chart-3";
import { reactive,computed } from "vue"

const props = defineProps({
    data:Object
});

const labels = computed(() => {
    return props.data.labels;
});

const totals = computed(() => {
    return props.data.totals;
});

Chart.register(...registerables);
const barData = reactive({
    labels: labels,
    datasets: [
        {
            label: '売上',
            data:
                totals,
            backgroundColor: "rgb(75, 192, 192)",
            tension: .1,
        }
    ]
})
</script>
<template>
    <div>
        <BarChart :chartData="barData"/>
    </div>

</template>
