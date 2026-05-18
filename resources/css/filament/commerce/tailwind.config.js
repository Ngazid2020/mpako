import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/Commerce/**/*.php',
        './resources/views/filament/commerce/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
}
