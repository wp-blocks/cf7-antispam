declare global {
	interface Window {
		wpcf7: () => void;
		canvasCount: number;
	}
	interface String {
		hashCode(): number;
	}

	interface Navigator {
		msMaxTouchPoints?: number;
		deviceMemory?: number;
	}

	let cf7a_settings: {
		prefix: string;
		version: string;
		disableReload: string;
	}

	let spamChartData: {
		lineData: any;
		pieData: any;
	}

	let cf7a_admin_settings: {
		alertMessage: string;
	}
}

export {}
