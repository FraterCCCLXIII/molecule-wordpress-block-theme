// src/theme.ts
import { createTheme } from '@mui/material/styles'
import { red } from '@mui/material/colors'

const theme = createTheme({
	palette: {
		mode: 'light',
		primary: { main: '#000000', contrastText: '#ffffff' },
		secondary: { main: '#6d6d6d', contrastText: '#000000' },
		error: { main: red.A400 },
		background: { default: '#ffffff', paper: '#ffffff' },
		text: { primary: '#1a1a1a', secondary: '#555' },
	},
	typography: { fontFamily: `"Open Sans", sans-serif` },
	components: {
		MuiButton: {
			styleOverrides: {
				root: ({ theme }) => ({
					'&.Mui-disabled': { backgroundColor: 'rgba(116, 118, 119)', color: '#fff' },
				}),
			},
		},
		MuiIconButton: {
			styleOverrides: {
				root: ({ theme }) => ({
					'&.Mui-disabled': { backgroundColor: 'rgba(0, 0, 0, .26)', color: 'rgba(116, 118, 119)' },
				}),
			},
		},
	},
	shape: {
		borderRadius: 5,
	},
})
export default theme
