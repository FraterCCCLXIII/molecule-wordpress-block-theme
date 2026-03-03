// First import to guarantee Redux stores are registered
import '@greenpay/store'
import './utils/eventEmitter'
import { usePluginReady } from '@greenpay/hooks'
import { registerPayment } from '@greenpay/utils'
import { Root } from 'react-dom/client'
import { createRoot, useEffect, useRef } from '@wordpress/element'
import GreenPayClassic from './app/GreenPayClassic'
import { MOUNT, ROOT } from './data/constants'

/**
 * Entry bootstrap function
 * Waits for settings to load into the client and routes rendering logic
 * for the different checkout contexts.
 */
const Bootstrap: React.FC = () => {
	const settings = usePluginReady()
	const classicRootRef = useRef<Root>()

	useEffect(() => {
		if (!settings) return
		
		// Use requestAnimationFrame to ensure DOM is fully ready
		const rafId = requestAnimationFrame(async () => {
			// Check if the classic checkout mount element exists
			// If it doesn't exist, proceed with rendering as a block checkout
			let container = document.getElementById(MOUNT)
			if (!container) {
				// Remove hacky jQuery event listener for block checkouts
				if (window.jQuery) window.jQuery(document.body).off('updated_checkout', observeAndMount)
				// Blocks registration. Doesn't need a react render as the function mounts the app
				await registerPayment(settings)
				window.dispatchEvent(new Event('greenpay:remount'))
			} else {
				// Always remount for updated_checkout events
				// WooCommerce replaces the entire DOM, so we need a fresh mount
				
				// Unmount any existing classic root
				if (classicRootRef.current) {
					classicRootRef.current.unmount()
				}
				
				// Create a fresh root for the new DOM element
				classicRootRef.current = createRoot(container)
				classicRootRef.current.render(
					<GreenPayClassic
						title={settings.title}
						description={settings.description}
						extraMessage={settings.extraMessage}
					/>,
				)
				container.dataset.reactRendered = 'true'
			}
		});

		return () => {
			cancelAnimationFrame(rafId);
			// Don't unmount the classic root here - let it persist
			// The unmounting should be handled by the mount function when needed
		}
	}, [settings])

	return null
}


let root: Root
let bootstrapRenderCount = 0;
let mountTimeout: ReturnType<typeof setTimeout> | null = null;
let observerInitialized = false;
let lastMountId: string | null = null;

/**
 * Initial mount to Bootstrap the plugin
 */
function mount() {
	// For updated_checkout events, we need to always remount
	// because WooCommerce replaces the entire checkout form DOM
	const container = document.getElementById(MOUNT);
	
	// we'll render our one React tree into a dummy wrapper
	let rootWrapper = document.getElementById(ROOT)
	if (!rootWrapper) {
		rootWrapper = document.createElement('div')
		rootWrapper.id = ROOT
		document.body.appendChild(rootWrapper)
	}

	if (!root) {
		// If the root doesn't exist, create it
		root = createRoot(rootWrapper)
	}

	// Force a new render with incremented key for updated_checkout events
	// This ensures React creates a fresh component instance
	bootstrapRenderCount++;
	root.render(<Bootstrap key={bootstrapRenderCount} />)
	
	// Set lastMountId after every mount
	if (container) {
		lastMountId = container.getAttribute('data-mount-id');
	}
}

function observeAndMount() {
    // Always reset reactRendered to false for updated_checkout events
    // This ensures we can remount when WooCommerce replaces the DOM
    const container = document.getElementById(MOUNT);
    if (container) {
        container.dataset.reactRendered = 'false';
    }
    
    // Debounce mount logic
    if (mountTimeout) clearTimeout(mountTimeout);
    mountTimeout = setTimeout(() => {
        mount();
    }, 50);

    if (!observerInitialized) {
        const observer = new MutationObserver((mutations, obs) => {
            // Check if any mutation affects the mount element
            const hasMountElementChange = mutations.some(mutation => {
                return Array.from(mutation.addedNodes).some(node => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        const element = node as Element;
                        return element.id === MOUNT || element.querySelector(`#${MOUNT}`);
                    }
                    return false;
                });
            });

            if (hasMountElementChange) {
                const container = document.getElementById(MOUNT);
                const currentMountId = container ? container.getAttribute('data-mount-id') : null;
                
                // Always remount on mount element changes (WooCommerce DOM replacement)
                if (container) {
                    if (mountTimeout) clearTimeout(mountTimeout);
                    mountTimeout = setTimeout(() => {
                        mount();
                        container.dataset.reactRendered = 'true';
                        lastMountId = currentMountId;
                        // Don't disconnect - keep observing for future changes
                    }, 50);
                }
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
        observerInitialized = true;
    }
}

// Initialize only once
let initializationComplete = false;

function initializeOnce() {
	if (initializationComplete) return;
	initializationComplete = true;
	
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			setTimeout(() => {
				observeAndMount()
			}, 10)
		})
	} else {
		observeAndMount()
	}
}

initializeOnce();

/**
 * Hacky jQuery for classic checkout
 */

if (window.jQuery) {
	window.jQuery(document.body).on('updated_checkout', () => {
		if (mountTimeout) clearTimeout(mountTimeout);
		mountTimeout = setTimeout(() => {
			mount();
		}, 50);
	});
}

window.greenpayRepaint = observeAndMount
window.greenpayBoot = mount
window.addEventListener(
	'greenpay:remount',
	() => {
		setTimeout(() => mount(), 100)
	},
	{ once: true },
)

