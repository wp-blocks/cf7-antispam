declare global {
	interface Window {
		wpcf7: () => void;
		canvasCount: number;
		cf7aInitCharts: (data?: any) => void;
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
	};

	let spamChartData: {
		dates?: string[];
		ham?: number[];
		spam?: number[];
		blockedIps?: number[];
		blockedComments?: number[];
		by_type?: {
			ham: number;
			spam: number;
		};
		countryData?: {
			labels: string[];
			data: number[];
		} | null;
	};

	let cf7a_admin_settings: {
		alertMessage: string;
		pieChartData?: Record<string, number>;
	};
}

export {};
