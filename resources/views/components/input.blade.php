@props(['type' => 'text'])

<input type="{{ $type }}" {{ $attributes->merge(['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent']) }}>
