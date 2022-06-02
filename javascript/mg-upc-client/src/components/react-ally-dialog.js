import { h } from 'preact';
import { useEffect, useState, useCallback } from "preact/hooks";
import { createPortal } from 'preact/compat';
import A11yDialogLib from 'a11y-dialog'

const useIsMounted = () => {
	const [isMounted, setIsMounted] = useState(false)

	useEffect(() => setIsMounted(true), [])

	return isMounted
}

const useA11yDialogInstance = () => {
	const [instance, setInstance] = useState(null)
	const container = useCallback(node => {
		if (node !== null) setInstance(new A11yDialogLib(node))
	}, [])

	return [instance, container]
}

export const useA11yDialog = props => {
	const [instance, ref] = useA11yDialogInstance();
	const close = useCallback(() => instance.hide(), [instance]);
	const role = props.role || 'dialog';
	const isAlertDialog = role === 'alertdialog';
	const titleId = props.titleId || props.id + '-title';

	// Destroy the `a11y-dialog` instance when unmounting the component.
	useEffect(() => {
		return () => {
			if (instance) instance.destroy();
		}
	}, [instance]);

	return [
		instance,
		{
			container: {
				id: props.id,
				ref,
				role,
				tabIndex: -1,
				'aria-modal': true,
				'aria-hidden': true,
				'aria-labelledby': titleId,
			},
			overlay: { onClick: isAlertDialog ? undefined : close },
			dialog: { role: 'document' },
			closeButton: { type: 'button', onClick: close },
			// Using a paragraph with accessibility mapping can be useful to work
			// around SEO concerns of having multiple <h1> per page.
			// See: https://twitter.com/goetsu/status/1261253532315004930
			title: { role: 'heading', 'aria-level': 1, id: titleId },
		},
	]
}

export const A11yDialog = props => {
	const isMounted = useIsMounted();
	const [instance, attributes] = useA11yDialog(props);
	const { dialogRef } = props;

	useEffect(() => {
		if (instance) dialogRef(instance);
		return () => dialogRef(undefined);
	}, [dialogRef, instance]);

	if (!isMounted) return null;

	const root = props.dialogRoot
		? document.querySelector(props.dialogRoot)
		: document.body;
	const title = (
		<h2 {...attributes.title} className={props.classNames.title} key='title'>
			{ props.onBack && (
				<a aria-label={props.backButtonLabel} href="#" onClick={ (e) => {e.preventDefault(); props.onBack(e)} }>&larr;</a>
			)} {props.title}
		</h2>
	);
	const button = (
		<button
			{...attributes.closeButton}
			className={props.classNames.closeButton}
			aria-label={props.closeButtonLabel}
			key='button'
		>
			{props.closeButtonContent}
		</button>
	);
	const children = [
		props.closeButtonPosition === 'first' && button,
		title,
		props.children,
		props.closeButtonPosition === 'last' && button,
	].filter(Boolean);

	return createPortal(
		<div {...attributes.container} className={props.classNames.container}>
			<div {...attributes.overlay} className={props.classNames.overlay} />
			<div {...attributes.dialog} className={props.classNames.dialog}>
				{children}
			</div>
		</div>,
		root
	);
}

A11yDialog.defaultProps = {
	role: 'dialog',
	closeButtonLabel: 'Close this dialog window',
	closeButtonContent: '\u00D7',
	closeButtonPosition: 'first',
	classNames: {},
	backButtonLabel: 'Back',
	dialogRef: () => void 0,
	// Default properties cannot be based on other properties, so the default
	// value for the `titleId` prop is defined in the `render(..)` method.
};
