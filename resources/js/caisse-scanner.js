document.addEventListener('alpine:init', () => {
    Alpine.data('caisseScanner', () => ({
        scanning: false,
        stream: null,
        detector: null,
        scanLoop: null,
        supported: ('BarcodeDetector' in window),

        async startScan() {
            if (!this.supported) return;
            try {
                this.detector = new BarcodeDetector({
                    formats: ['ean_13', 'ean_8', 'code_128', 'qr_code', 'code_39'],
                });
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' },
                });
                this.$refs.camvideo.srcObject = this.stream;
                this.scanning = true;
                this.scanLoop = setInterval(this.detect.bind(this), 300);
            } catch (e) {
                this.scanning = false;
            }
        },

        async detect() {
            const v = this.$refs.camvideo;
            if (!v || v.readyState < 2) return;
            try {
                const codes = await this.detector.detect(v);
                if (codes.length) {
                    this.stopScan();
                    this.$wire.set('search', codes[0].rawValue);
                }
            } catch (e) {}
        },

        stopScan() {
            clearInterval(this.scanLoop);
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
            }
            this.stream = null;
            this.scanning = false;
        },
    }));
});
