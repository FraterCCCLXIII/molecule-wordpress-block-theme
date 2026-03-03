import { useCallback, useState } from '@wordpress/element'

/**
 * Helper function to toggle visibility of input values
 * @param initialValue - sets the starting state of visibility, false -> hide, true -> show
 */
export function visibilityToggle(initialValue = false): [boolean, () => void] {
	const [value, setValue] = useState(initialValue)
	const toggle = useCallback(() => {
		setValue((v) => !v)
	}, [])
	return [value, toggle]
}
