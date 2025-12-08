import { h, Fragment } from 'preact';

export const SimpleContainer = props => {
 const title = (<>
   {(props.title || props.onBack) && (<h2 className={props.classNames.title}>
   {props.onBack && (
    <a className={props.classNames.back} aria-label={props.backButtonLabel} href="#"
       onClick={(e) => {e.preventDefault(); props.onBack(e);}}>&larr;</a>
   )} {props.title}
  </h2>)}
 </>);

 return (
  <div className={props.classNames.container}>
   {title}
   <div className={props.classNames.content}>
    {props.children}
   </div>
  </div>
 );
}

SimpleContainer.defaultProps = {
 classNames: {
  container: 'simple-upc-container',
  title: 'simple-upc-container-title',
  content: 'simple-upc-container-content',
  back: 'simple-upc-container-back',
 },
 backButtonLabel: 'Back',
};