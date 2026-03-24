<div class="cards">
    @foreach ($cards as $card)
        <div class="card summary-card">
            <div class="summary-label">{{ $card['label'] }}</div>
            <div class="summary-value">{{ $card['value'] }}</div>
        </div>
    @endforeach
</div>
