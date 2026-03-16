/**
 * Community Kit — Wizard Shell
 *
 * Full-screen overlay that hosts module wizard steps. Provides a shared
 * context (WizardContext) that step components use to read/write state,
 * navigate between steps, and persist progress via the REST API.
 *
 * Module wizard bundles register their steps on the global
 * window.ckWizardSteps object keyed by step ID. The shell reads the
 * step list from ckWizardShell.activeWizard.steps and renders them
 * in order.
 *
 * @package CommunityKit
 */

/* global ckWizardShell */

const { createElement, useState, useEffect, useCallback, createContext, useContext, render } = wp.element;
const { Button, Spinner } = wp.components;
const { __ } = wp.i18n;
const apiFetch = wp.apiFetch;

/**
 * Wizard context — shared state for all step components.
 */
const WizardContext = createContext();

/**
 * Hook for step components to access wizard context.
 *
 * @return {Object} Wizard context value.
 */
function useWizard() {
	return useContext( WizardContext );
}

// Export for module wizard bundles.
window.CKWizardContext = WizardContext;
window.useWizard = useWizard;

/**
 * WizardShell — the main wizard component.
 */
function WizardShell() {
	const wizard = ckWizardShell.activeWizard;
	const steps = wizard.steps;

	const [ currentStep, setCurrentStep ] = useState( 0 );
	const [ wizardState, setWizardState ] = useState( {} );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ visible, setVisible ] = useState( true );
	const [ paused, setPaused ] = useState( false );

	// Load saved state on mount.
	useEffect( function () {
		apiFetch( {
			path: 'community-kit/v1/wizard/' + wizard.module + '/state',
		} ).then( function ( response ) {
			if ( response.state && Object.keys( response.state ).length > 0 ) {
				setWizardState( response.state );
				// Resume from saved step if available.
				if ( response.state._currentStep !== undefined ) {
					var savedStep = parseInt( response.state._currentStep, 10 );
					if ( savedStep >= 0 && savedStep < steps.length ) {
						setCurrentStep( savedStep );
					}
				}
			}
			setLoading( false );
		} ).catch( function () {
			setLoading( false );
		} );
	}, [] );

	/**
	 * Persist state to the REST API.
	 */
	const saveState = useCallback( function ( newState ) {
		setSaving( true );
		var stateToSave = Object.assign( {}, newState, { _currentStep: currentStep.toString() } );
		apiFetch( {
			path: 'community-kit/v1/wizard/' + wizard.module + '/state',
			method: 'POST',
			data: { state: stateToSave },
		} ).then( function () {
			setSaving( false );
		} ).catch( function () {
			setSaving( false );
		} );
	}, [ wizard.module, currentStep ] );

	/**
	 * Update a value in wizard state (used by step components).
	 */
	const updateState = useCallback( function ( key, value ) {
		setWizardState( function ( prev ) {
			var next = Object.assign( {}, prev );
			next[ key ] = value;
			return next;
		} );
	}, [] );

	/**
	 * Go to next step and save state.
	 */
	const goNext = useCallback( function () {
		if ( currentStep < steps.length - 1 ) {
			var nextStep = currentStep + 1;
			setCurrentStep( nextStep );
			var stateToSave = Object.assign( {}, wizardState, { _currentStep: nextStep.toString() } );
			saveState( stateToSave );
		}
	}, [ currentStep, steps.length, wizardState, saveState ] );

	/**
	 * Go to previous step.
	 */
	const goBack = useCallback( function () {
		if ( currentStep > 0 ) {
			setCurrentStep( currentStep - 1 );
		}
	}, [ currentStep ] );

	/**
	 * Skip the wizard (close without completing).
	 * Saves state and dismisses so it won't auto-launch again.
	 */
	const skip = useCallback( function () {
		saveState( wizardState );
		apiFetch( {
			path: 'community-kit/v1/wizard/' + wizard.module + '/dismiss',
			method: 'POST',
		} ).finally( function () {
			setVisible( false );
		} );
	}, [ wizardState, saveState, wizard.module ] );

	/**
	 * Mark wizard as complete and close.
	 */
	const complete = useCallback( function () {
		apiFetch( {
			path: 'community-kit/v1/wizard/' + wizard.module + '/complete',
			method: 'POST',
		} ).then( function () {
			setVisible( false );
		} );
	}, [ wizard.module ] );

	if ( ! visible ) {
		return null;
	}

	// Build context value.
	var contextValue = {
		state: wizardState,
		updateState: updateState,
		saveState: function () { saveState( wizardState ); },
		goNext: goNext,
		goBack: goBack,
		skip: skip,
		complete: complete,
		currentStep: currentStep,
		totalSteps: steps.length,
		stepId: steps[ currentStep ],
		module: wizard.module,
		paused: paused,
		setPaused: setPaused,
		saving: saving,
	};

	// Find the step component.
	var StepComponent = null;
	if ( window.ckWizardSteps && window.ckWizardSteps[ steps[ currentStep ] ] ) {
		StepComponent = window.ckWizardSteps[ steps[ currentStep ] ];
	}

	return createElement(
		WizardContext.Provider,
		{ value: contextValue },
		createElement(
			'div',
			{ className: 'ck-wizard-overlay' },
			createElement(
				'div',
				{ className: 'ck-wizard-shell' },
				// Header.
				createElement(
					'div',
					{ className: 'ck-wizard-shell__header' },
					createElement(
						'div',
						{ className: 'ck-wizard-shell__progress' },
						__( 'Step ', 'community-kit' ) + ( currentStep + 1 ) + __( ' of ', 'community-kit' ) + steps.length
					),
					createElement(
						Button,
						{
							variant: 'link',
							className: 'ck-wizard-shell__skip',
							onClick: skip,
						},
						__( 'Save and exit', 'community-kit' )
					)
				),
				// Progress bar.
				createElement(
					'div',
					{ className: 'ck-wizard-shell__progress-bar' },
					createElement( 'div', {
						className: 'ck-wizard-shell__progress-fill',
						style: { width: ( ( ( currentStep + 1 ) / steps.length ) * 100 ) + '%' },
					} )
				),
				// Body.
				createElement(
					'div',
					{ className: 'ck-wizard-shell__body' },
					loading
						? createElement(
							'div',
							{ className: 'ck-wizard-shell__loading' },
							createElement( Spinner, null ),
							createElement( 'p', null, __( 'Loading...', 'community-kit' ) )
						)
						: StepComponent
							? createElement( StepComponent, null )
							: createElement(
								'p',
								null,
								__( 'Step component not found: ', 'community-kit' ) + steps[ currentStep ]
							)
				),
				// Footer.
				! loading && createElement(
					'div',
					{ className: 'ck-wizard-shell__footer' },
					currentStep > 0 && createElement(
						Button,
						{
							variant: 'secondary',
							onClick: goBack,
							disabled: paused,
						},
						__( 'Back', 'community-kit' )
					),
					// Spacer.
					createElement( 'div', { className: 'ck-wizard-shell__spacer' } ),
					saving && createElement(
						'span',
						{ className: 'ck-wizard-shell__saving' },
						__( 'Saving...', 'community-kit' )
					)
				)
			)
		)
	);
}

// Mount the wizard.
document.addEventListener( 'DOMContentLoaded', function () {
	var root = document.getElementById( 'ck-wizard-root' );
	if ( root ) {
		render( createElement( WizardShell, null ), root );
	}
} );
