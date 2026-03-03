/**
 * Typed CSS Modules import for SCSS files ending in `.module.scss`.
 *
 * This declaration allows you to import a SCSS module and
 * get a mapping of its class names to the generated scoped class names.
 *
 * Example Usage:
 * ```ts
 * import styles from './Component.module.scss';
 *
 * // In JSX:
 * <div className={styles.container}>Hello</div>
 * ```
 *
 * @module '*.module.scss'
 */

declare module '*.module.scss' {
	/**
	 * A map of original class names to generated scoped class names.
	 */
	const classes: { [key: string]: string }
	/**
	 * Exports the classes mapping object as the module's default export.
	 */
	export = classes
}
