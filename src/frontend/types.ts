// Extend navigator to support msMaxTouchPoints and deviceMemory
export interface ExtendedNavigator extends Navigator {
	msMaxTouchPoints?: number;
	deviceMemory?: number;
}

export interface Tests {
	timezone: string | null;
	platform: string | null;
	screens: number[] | null;
	memory: number | null;
	user_agent: string | null;
	app_version: string | null;
	webdriver: boolean | null;
	session_storage: number | null;
	touch?: boolean | null;
	isFFox?: boolean | null;
	isSamsung?: boolean | null;
	isOpera?: boolean | null;
	isIE?: boolean | null;
	isIELegacy?: boolean | null;
	isEdge?: boolean | null;
	isChrome?: boolean | null;
	isSafari?: boolean | null;
	isUnknown?: boolean | null;
	isIos?: boolean | null;
	isAndroid?: boolean | null;
}
