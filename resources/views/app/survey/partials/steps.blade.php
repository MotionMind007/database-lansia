<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-5">
    <div class="flex items-start" id="step-indicators">
        @php
            $steps = [
                ['num' => 1, 'label' => 'Data Awal'],
                ['num' => 2, 'label' => 'Identitas'],
                ['num' => 3, 'label' => 'RT & Pendidikan'],
                ['num' => 4, 'label' => 'Pekerjaan & KTP'],
                ['num' => 5, 'label' => 'Pangan & Kesehatan'],
                ['num' => 6, 'label' => 'Perumahan & Info'],
                ['num' => 7, 'label' => 'Sosial'],
                ['num' => 8, 'label' => 'Pengeluaran'],
                ['num' => 9, 'label' => 'Dokumen & Submit'],
            ];
        @endphp
        @foreach($steps as $i => $step)
        <div class="step-item {{ $i === 0 ? 'active' : '' }}" id="step-indicator-{{ $step['num'] }}">
            <div class="step-circle" id="step-circle-{{ $step['num'] }}">{{ $step['num'] }}</div>
            <span class="step-label">{{ $step['label'] }}</span>
        </div>
        @endforeach
    </div>
</div>
