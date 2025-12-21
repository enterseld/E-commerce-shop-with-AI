<template>
  <div class="w-full h-full p-6 bg-gradient-to-br from-blue-50 to-indigo-50">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-bold text-gray-800 mb-2">Аналіз та прогнозування продажів</h1>
      <p class="text-gray-600 mb-6">Поліноміальна регресія {{ degree }}-го степеня</p>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-md">
          <p class="text-sm text-gray-600">Коефіцієнт детермінації</p>
          <p class="text-2xl font-bold text-blue-600">R² = {{ metrics.r2.toFixed(4) }}</p>
          <p class="text-xs text-gray-500 mt-1">
            {{ r2Quality }}
          </p>
        </div>

        <div class="bg-white p-4 rounded-lg shadow-md">
          <p class="text-sm text-gray-600">Середньоквадратична похибка</p>
          <p class="text-2xl font-bold text-green-600">{{ formatCurrency(metrics.rmse) }}</p>
          <p class="text-xs text-gray-500 mt-1">RMSE - похибка прогнозу</p>
        </div>

        <div class="bg-white p-4 rounded-lg shadow-md">
          <label class="text-sm text-gray-600 block mb-2">Степінь полінома</label>
          <select
            v-model.number="degree"
            class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option :value="2">Квадратична (2)</option>
            <option :value="3">Кубічна (3)</option>
            <option :value="4">Четвертого степеня (4)</option>
            <option :value="5">П'ятого степеня (5)</option>
          </select>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Графік продажів та прогноз</h2>
        <v-chart :option="chartOptions" autoresize style="height: 400px" />
      </div>

      <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Статистика по місяцях</h2>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-100">
              <tr>
                <th class="p-3 text-left">Місяць</th>
                <th class="p-3 text-right">Фактичні продажі</th>
                <th class="p-3 text-right">Прогноз</th>
                <th class="p-3 text-right">Відхилення</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, i) in forecastData"
                :key="i"
                :class="item.isForecast ? 'bg-green-50' : 'hover:bg-gray-50'"
              >
                <td class="p-3 border-t">
                  {{ item.month }}
                  <span
                    v-if="item.isForecast"
                    class="ml-2 text-xs text-green-600 font-semibold"
                  >(прогноз)</span>
                </td>
                <td class="p-3 border-t text-right font-medium">
                  {{ item.actual ? formatCurrency(item.actual) : '-' }}
                </td>
                <td class="p-3 border-t text-right text-green-600">
                  {{ formatCurrency(item.predicted) }}
                </td>
                <td class="p-3 border-t text-right">
                  <span
                    v-if="item.actual"
                    :class="Math.abs(item.actual - item.predicted) / item.actual < 0.1
                      ? 'text-green-600'
                      : 'text-orange-600'"
                  >
                    {{ ((item.predicted - item.actual) / item.actual * 100).toFixed(1) }}%
                  </span>
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <h3 class="font-semibold text-blue-900 mb-2">💡 Пояснення метрик:</h3>
        <ul class="text-sm text-blue-800 space-y-1">
          <li><strong>R²:</strong> показує, наскільки добре модель пояснює варіацію даних (0–1, краще ближче до 1)</li>
          <li><strong>RMSE:</strong> середня похибка прогнозу в грошовому вираженні</li>
          <li><strong>Поліноміальна регресія:</strong> гнучка модель для нелінійних трендів</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
    import { ref, watch, computed, onMounted } from "vue";
    import { use } from "echarts/core";
    import { LineChart } from "echarts/charts";
    import { GridComponent, TooltipComponent, LegendComponent } from "echarts/components";
    import { CanvasRenderer } from "echarts/renderers";
    import VChart from "vue-echarts";

    use([LineChart, GridComponent, TooltipComponent, LegendComponent, CanvasRenderer]);

    const degree = ref(3);
    const data = ref([]);
    const forecastData = ref([]);
    const metrics = ref({ r2: 0, rmse: 0 });

    // Дані замовлень

    const rawOrders = [
    { id: 91, total: 2367.0, status: "Open", created_at: "2025-08-07" },
    { id: 92, total: 1073.0, status: "Open", created_at: "2025-08-07" },
    { id: 93, total: 6312.0, status: "Open", created_at: "2025-08-07" },
    { id: 94, total: 3440.0, status: "Open", created_at: "2025-08-07" },
    { id: 95, total: 2367.0, status: "Open", created_at: "2025-08-07" },
    { id: 96, total: 4734.0, status: "Open", created_at: "2025-08-07" },
    { id: 97, total: 2367.0, status: "Paid", created_at: "2025-08-07" },
    { id: 98, total: 3015.0, status: "Open", created_at: "2025-08-29" },
    { id: 99, total: 3015.0, status: "Paid", created_at: "2025-08-29" },
    { id: 100, total: 2367.0, status: "Paid", created_at: "2025-08-29" },
    { id: 101, total: 2367.0, status: "Paid", created_at: "2025-09-02" },
    { id: 102, total: 3015.0, status: "Open", created_at: "2025-10-08" },
    ];

    const aggregateByMonth = (orders) => {
    const monthMap = {};
    orders.forEach((o) => {
        const d = new Date(o.created_at);
        const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
        if (!monthMap[key]) monthMap[key] = { total: 0, count: 0 };
        monthMap[key].total += parseFloat(o.total);
        monthMap[key].count++;
    });
    return Object.keys(monthMap)
        .sort()
        .map((k, i) => ({
        month: k,
        monthIndex: i,
        total: monthMap[k].total,
        count: monthMap[k].count,
        average: monthMap[k].total / monthMap[k].count,
        }));
    };

    // Метод Гауса
    const gaussianElimination = (matrix, vector) => {
    const n = matrix.length;
    const aug = matrix.map((row, i) => [...row, vector[i]]);
    for (let i = 0; i < n; i++) {
        let maxRow = i;
        for (let k = i + 1; k < n; k++)
        if (Math.abs(aug[k][i]) > Math.abs(aug[maxRow][i])) maxRow = k;
        [aug[i], aug[maxRow]] = [aug[maxRow], aug[i]];
        for (let k = i + 1; k < n; k++) {
        const f = aug[k][i] / aug[i][i];
        for (let j = i; j <= n; j++) aug[k][j] -= f * aug[i][j];
        }
    }
    const sol = new Array(n);
    for (let i = n - 1; i >= 0; i--) {
        sol[i] = aug[i][n];
        for (let j = i + 1; j < n; j++) sol[i] -= aug[i][j] * sol[j];
        sol[i] /= aug[i][i];
    }
    return sol;
    };

    const polynomialRegression = (data, degree) => {
    const n = data.length;
    const X = data.map((d) => d.monthIndex);
    const Y = data.map((d) => d.total);
    const matrix = [];
    const vector = [];
    for (let i = 0; i <= degree; i++) {
        const row = [];
        for (let j = 0; j <= degree; j++) {
        let sum = 0;
        for (let k = 0; k < n; k++) sum += Math.pow(X[k], i + j);
        row.push(sum);
        }
        matrix.push(row);
        let s = 0;
        for (let k = 0; k < n; k++) s += Y[k] * Math.pow(X[k], i);
        vector.push(s);
    }
    return gaussianElimination(matrix, vector);
    };

    const predictValue = (coeffs, x) => coeffs.reduce((s, c, p) => s + c * Math.pow(x, p), 0);

    const calculateMetrics = (actual, predicted) => {
    const n = actual.length;
    const mean = actual.reduce((s, v) => s + v, 0) / n;
    let ssRes = 0,
        ssTot = 0;
    for (let i = 0; i < n; i++) {
        ssRes += Math.pow(actual[i] - predicted[i], 2);
        ssTot += Math.pow(actual[i] - mean, 2);
    }
    return { r2: 1 - ssRes / ssTot, rmse: Math.sqrt(ssRes / n) };
    };

    const formatCurrency = (v) =>
    new Intl.NumberFormat("uk-UA", { style: "currency", currency: "UAH", minimumFractionDigits: 0 }).format(v);

    const updateModel = () => {
    const monthly = aggregateByMonth(rawOrders);
    data.value = monthly;
    const coeffs = polynomialRegression(monthly, degree.value);
    const lastIndex = monthly[monthly.length - 1].monthIndex;
    const forecastMonths = 12;
    const forecast = [];
    const predicted = monthly.map((d) => predictValue(coeffs, d.monthIndex));
    monthly.forEach((d, i) => forecast.push({ month: d.month, actual: d.total, predicted: predicted[i] }));
    for (let i = 1; i <= forecastMonths; i++) {
        const idx = lastIndex + i;
        const val = Math.max(0, predictValue(coeffs, idx));
        const lastDate = new Date(monthly[monthly.length - 1].month);
        const fut = new Date(lastDate.getFullYear(), lastDate.getMonth() + i, 1);
        const month = `${fut.getFullYear()}-${String(fut.getMonth() + 1).padStart(2, "0")}`;
        forecast.push({ month, predicted: val, isForecast: true });
    }
    forecastData.value = forecast;
    metrics.value = calculateMetrics(monthly.map((d) => d.total), predicted);
    };

    watch(degree, updateModel, { immediate: true });

    const chartOptions = computed(() => ({
    tooltip: { trigger: "axis" },
    legend: { data: ["Фактичні дані", "Прогноз"] },
    grid: { left: "3%", right: "4%", bottom: "10%", containLabel: true },
    xAxis: { type: "category", data: forecastData.value.map((d) => d.month) },
    yAxis: {
        type: "value",
        axisLabel: { formatter: (v) => `${(v / 1000).toFixed(0)}k` },
    },
    series: [
        {
        name: "Фактичні дані",
        type: "line",
        smooth: true,
        data: forecastData.value.map((d) => d.actual ?? null),
        symbolSize: 8,
        lineStyle: { width: 3 },
        },
        {
        name: "Прогноз",
        type: "line",
        smooth: true,
        data: forecastData.value.map((d) => d.predicted),
        symbol: "circle",
        symbolSize: 5,
        lineStyle: { width: 2, type: "dashed" },
        },
    ],
    }));

    const r2Quality = computed(() =>
    metrics.value.r2 > 0.9
        ? "Відмінна точність моделі"
        : metrics.value.r2 > 0.7
        ? "Хороша точність моделі"
        : "Задовільна точність моделі"
    );
</script>

<style scoped>
.bg-gradient-to-br {
  background: linear-gradient(to bottom right, #eff6ff, #eef2ff);
}
</style>
