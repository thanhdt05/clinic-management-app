<script setup lang="ts">
import { computed, ref } from 'vue'
import type { TopDoctor, TopPrescribedMedicine } from '@/types/stats'

const props = defineProps<{
  topMedicines?: TopPrescribedMedicine[]
  topDoctors?: TopDoctor[]
  loading?: boolean
}>()

const activeTab = ref<'medicines' | 'doctors'>('medicines')

const maxMedicineQuantity = computed(() => {
  if (!props.topMedicines || props.topMedicines.length === 0) return 1
  return Math.max(...props.topMedicines.map((m) => Number(m.total_prescribed)), 1)
})

const maxDoctorCount = computed(() => {
  if (!props.topDoctors || props.topDoctors.length === 0) return 1
  return Math.max(...props.topDoctors.map((d) => Number(d.examination_count)), 1)
})

const colors = ['bg-orange-500', 'bg-cyan-500', 'bg-pink-500', 'bg-green-500', 'bg-purple-500']
const textColors = ['text-orange-500', 'text-cyan-500', 'text-pink-500', 'text-green-500', 'text-purple-500']
</script>

<template>
  <div class="card h-full">
    <div class="flex justify-between items-center mb-6">
      <div class="font-semibold text-xl">Top Performers</div>
      <div class="flex gap-1 p-1 bg-surface-100 dark:bg-surface-800 rounded-lg">
        <Button
          size="small"
          :severity="activeTab === 'medicines' ? 'primary' : 'secondary'"
          :text="activeTab !== 'medicines'"
          label="Medicines"
          icon="pi pi-box"
          @click="activeTab = 'medicines'"
        />
        <Button
          size="small"
          :severity="activeTab === 'doctors' ? 'primary' : 'secondary'"
          :text="activeTab !== 'doctors'"
          label="Doctors"
          icon="pi pi-user-md"
          @click="activeTab = 'doctors'"
        />
      </div>
    </div>

    <!-- Medicines List -->
    <div v-if="activeTab === 'medicines'">
      <div v-if="loading" class="py-8 flex justify-center">
        <ProgressSpinner style="width: 40px; height: 40px" />
      </div>
      <div v-else-if="!topMedicines || topMedicines.length === 0" class="py-8 text-center text-muted-color">
        <i class="pi pi-box text-3xl mb-2"></i>
        <div>No prescriptions recorded yet</div>
      </div>
      <ul v-else class="list-none p-0 m-0">
        <li
          v-for="(med, idx) in topMedicines"
          :key="med.id"
          class="flex flex-col md:flex-row md:items-center md:justify-between mb-5 last:mb-0"
        >
          <div class="flex items-center gap-3">
            <span class="w-6 text-center font-bold text-muted-color">#{{ idx + 1 }}</span>
            <div>
              <span class="text-surface-900 dark:text-surface-0 font-medium">{{ med.name }}</span>
              <div class="text-xs text-muted-color">Unit: {{ med.unit }}</div>
            </div>
          </div>
          <div class="mt-2 md:mt-0 flex items-center justify-end">
            <div class="bg-surface-200 dark:bg-surface-700 rounded-full overflow-hidden w-28 md:w-32" style="height: 8px">
              <div
                :class="colors[idx % colors.length]"
                class="h-full rounded-full transition-all duration-500"
                :style="{ width: `${Math.round((Number(med.total_prescribed) / maxMedicineQuantity) * 100)}%` }"
              ></div>
            </div>
            <span :class="textColors[idx % textColors.length]" class="ml-4 font-semibold min-w-12 text-right">
              {{ med.total_prescribed }}
            </span>
          </div>
        </li>
      </ul>
    </div>

    <!-- Doctors List -->
    <div v-if="activeTab === 'doctors'">
      <div v-if="loading" class="py-8 flex justify-center">
        <ProgressSpinner style="width: 40px; height: 40px" />
      </div>
      <div v-else-if="!topDoctors || topDoctors.length === 0" class="py-8 text-center text-muted-color">
        <i class="pi pi-user-md text-3xl mb-2"></i>
        <div>No examinations completed this month</div>
      </div>
      <ul v-else class="list-none p-0 m-0">
        <li
          v-for="(doc, idx) in topDoctors"
          :key="doc.id"
          class="flex flex-col md:flex-row md:items-center md:justify-between mb-5 last:mb-0"
        >
          <div class="flex items-center gap-3">
            <span class="w-6 text-center font-bold text-muted-color">#{{ idx + 1 }}</span>
            <div>
              <span class="text-surface-900 dark:text-surface-0 font-medium">{{ doc.doctor_name }}</span>
              <div class="text-xs text-muted-color">Medical Doctor</div>
            </div>
          </div>
          <div class="mt-2 md:mt-0 flex items-center justify-end">
            <div class="bg-surface-200 dark:bg-surface-700 rounded-full overflow-hidden w-28 md:w-32" style="height: 8px">
              <div
                :class="colors[idx % colors.length]"
                class="h-full rounded-full transition-all duration-500"
                :style="{ width: `${Math.round((Number(doc.examination_count) / maxDoctorCount) * 100)}%` }"
              ></div>
            </div>
            <span :class="textColors[idx % textColors.length]" class="ml-4 font-semibold min-w-12 text-right">
              {{ doc.examination_count }} visits
            </span>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
