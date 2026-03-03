import { dispatch, select, useDispatch, useSelect } from '@wordpress/data'

/**
 * Hook helper for dispatches in redux
 * Used in thunk/resolver, utility, or non react code
 * @param storeName
 * @returns
 */
export const dispatchStore = <T>(storeName: string): T => {
	return dispatch(storeName) as T
}

/**
 * Hook helper for selectors in redux
 * Used in thunk/resolver, utility, or non react code
 * @param storeName
 * @returns
 */
export const selectStore = <T>(storeName: string): T => {
	return select(storeName) as T
}

/**
 * Hook helper for dispatches in redux
 * Mainly used in react components and hooks. Allows react to set DOM updates to the store
 * @param storeName
 * @returns
 */
export const useDispatchStore = <T>(storeName: string): T => {
	return useDispatch(storeName) as T
}

/**
 * Hook helper for selectors in redux
 * Mainly used in react components and hooks. Allows react to rerender the DOM on store changes
 * @param storeName
 * @returns
 */
export const useSelectStore = <T>(storeName: string): T => {
	return useSelect((select) => select(storeName) as T, [])
}
